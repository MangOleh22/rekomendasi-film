// Keranjang belanja: daftar film, ringkasan (jumlah & total), pilih metode bayar, checkout.
import { api } from '../lib/api';
import { badge, btn, card, empty, esc, icon, iconBtn, on, openDialog, pageHeader, poster, toast } from '../lib/ui';

const row = (k, v, cls = '') => `<div class="flex justify-between gap-4 py-1.5 text-sm ${cls}"><span class="text-muted-foreground">${k}</span><span class="text-right font-medium">${v}</span></div>`;

const item = ({ movie: m }) => `
  <div class="${card} flex items-center gap-4 p-3">
    <a href="/" class="h-24 w-16 shrink-0 overflow-hidden rounded-md">${poster(m)}</a>
    <div class="min-w-0 flex-1">
      <p class="truncate font-semibold">${esc(m.title)}</p>
      <p class="truncate text-xs text-muted-foreground">${m.genres.map((g) => esc(g.name)).join(' · ')} · ${m.year}</p>
      <p class="mt-1 font-semibold text-emerald-600 dark:text-emerald-400">${esc(m.formatted_price)}</p>
    </div>
    ${iconBtn('trash', 'Hapus dari keranjang', `data-remove="${m.id}"`)}
  </div>`;

const invoice = (r) => `
  <div class="print-area rounded-lg border p-4">
    <p class="pb-2 text-center font-bold">RekomFilm</p>
    ${row('No. Faktur', esc(r.invoice_number))}
    ${r.transactions.map((t) => row(esc(t.movie.title), esc(t.formatted_amount))).join('')}
    ${row('Jumlah film', r.count)}${row('Metode', esc(r.transactions[0]?.payment_method))}
    <div class="flex justify-between border-t pt-3 font-bold"><span>Total</span><span>${esc(r.formatted_total)}</span></div>
  </div>
  <div class="mt-4 grid grid-cols-2 gap-2">
    <button onclick="print()" class="${btn('outline')}">${icon('printer')} Cetak</button>
    <a href="/" class="${btn()}">Kembali ke beranda</a>
  </div>`;

export default async function (root) {
  const load = async () => {
    const cart = await api.get('/cart');
    window.dispatchEvent(new Event('cart:changed'));

    root.innerHTML = pageHeader('Keranjang', 'Kumpulkan film, lalu bayar sekaligus.');
    if (!cart.count) {
      root.innerHTML += empty('Keranjang masih kosong', 'Buka detail film di beranda lalu pilih "Tambah ke keranjang".') +
        `<div class="mt-4 text-center"><a href="/" class="${btn()}">Jelajahi film</a></div>`;
      return;
    }

    root.innerHTML += `
      <div class="grid items-start gap-6 lg:grid-cols-[1fr_360px]">
        <div class="grid gap-3">${cart.data.map(item).join('')}</div>
        <form id="checkout" class="${card} grid gap-4 p-5 lg:sticky lg:top-20">
          <h2 class="font-semibold">Ringkasan pembayaran</h2>
          <div class="divide-y">
            ${row('Jumlah film', badge(`${cart.count} film`, 'secondary'))}
            ${row('Subtotal', esc(cart.formatted_total))}
            ${row('Biaya layanan', 'Gratis')}
            ${row('Total bayar', `<span class="text-lg font-bold">${esc(cart.formatted_total)}</span>`, 'items-center pt-3')}
          </div>
          <fieldset class="grid gap-2"><legend class="mb-1 text-sm font-medium">Metode pembayaran</legend>
            ${cart.methods.map((m, i) => `
              <label class="cursor-pointer"><input type="radio" name="payment_method" value="${esc(m)}" ${i === 0 ? 'checked' : ''} class="peer sr-only">
                <span class="flex items-center gap-3 rounded-md border px-3 py-2.5 text-sm transition-colors peer-checked:border-primary peer-checked:bg-primary/10 peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50">${icon('card')}${esc(m)}</span></label>`).join('')}
          </fieldset>
          <button class="${btn()}">${icon('check')} Bayar sekarang</button>
        </form>
      </div>`;
  };

  on(root, 'click', '[data-remove]', async (e, el) => {
    try { await api.del(`/cart/${el.dataset.remove}`); toast('Film dihapus dari keranjang.'); load(); } catch (err) { toast(err.message, 'error'); }
  });

  root.addEventListener('submit', async (e) => {
    e.preventDefault();
    const b = e.target.querySelector('button:not([type=button])');
    b.disabled = true;
    try {
      const res = await api.post('/cart/checkout', { payment_method: new FormData(e.target).get('payment_method') });
      window.dispatchEvent(new Event('cart:changed'));
      openDialog({ title: 'Pembayaran berhasil', description: esc(res.message), body: invoice(res), onClose: load });
    } catch (err) { toast(err.message, 'error'); b.disabled = false; }
  });

  load();
}
