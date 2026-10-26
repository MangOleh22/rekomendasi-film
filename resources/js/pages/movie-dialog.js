// Dialog detail film: info, beli (pembayaran), dan tandai ditonton. Dipakai halaman Beranda.
import { api } from '../lib/api';
import { $, ageBadge, badge, btn, esc, icon, on, openDialog, poster, select, toast } from '../lib/ui';

const METHODS = ['QRIS', 'BCA Virtual Account', 'Mandiri Virtual Account'];

const detail = (m) => `
  <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
    ${ageBadge(m)}<span class="flex items-center gap-1 text-amber-500">${icon('star', 'size-3.5 fill-current')}${Number(m.rating).toFixed(1)}</span><span>${m.year}</span>
  </div>
  <div class="grid gap-4 sm:grid-cols-[180px_1fr]">
    <div class="mx-auto aspect-[2/3] w-40 overflow-hidden rounded-lg sm:w-full">${poster(m)}</div>
    <div class="grid content-start gap-3">
      <div class="flex flex-wrap gap-1.5">${m.genres.map((g) => badge(esc(g.name))).join('')}</div>
      <p class="text-sm leading-relaxed text-muted-foreground">${esc(m.synopsis || 'Belum ada sinopsis.')}</p>
      <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${esc(m.formatted_price)}</p>
      ${m.is_purchased
        ? `<div class="grid gap-2">${badge(`${icon('check')} Film dimiliki`, 'success')}
             <button data-act="watch" class="${btn()}">${icon('play')} ${m.is_watched ? 'Tonton lagi' : 'Tonton sekarang'}</button></div>`
        : `<div class="grid gap-2"><select id="method" class="${select}">${METHODS.map((x) => `<option>${x}</option>`).join('')}</select>
             <button data-act="buy" class="${btn()}">${icon('card')} Beli sekarang · ${esc(m.formatted_price)}</button>
             <button data-act="cart" class="${btn('outline')}">${icon('cart')} Tambah ke keranjang</button></div>`}
    </div>
  </div>`;

export async function showMovie(id, onChanged) {
  const dlg = openDialog({ title: 'Memuat…', body: '<div class="h-48 animate-pulse rounded-md bg-accent"></div>', wide: true });
  const load = async () => {
    const m = (await api.get(`/movies/${id}`)).data;
    $('h2', dlg.root).textContent = m.title;
    dlg.el.innerHTML = detail(m);
  };
  try { await load(); } catch (e) { toast(e.message, 'error'); return dlg.close(); }

  on(dlg.el, 'click', '[data-act]', async (e, btnEl) => {
    btnEl.disabled = true;
    try {
      const act = btnEl.dataset.act;
      const res = act === 'buy' ? await api.post(`/movies/${id}/purchase`, { payment_method: $('#method', dlg.el).value })
        : act === 'cart' ? await api.post(`/cart/${id}`)
        : await api.post(`/movies/${id}/watch`);
      toast(res.message || 'Berhasil.');
      window.dispatchEvent(new Event('cart:changed'));
      if (act === 'cart') return (btnEl.disabled = false);
      await load();
      onChanged?.();
    } catch (err) { toast(err.message, 'error'); btnEl.disabled = false; }
  });
}
