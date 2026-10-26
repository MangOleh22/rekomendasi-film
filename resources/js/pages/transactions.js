import { api } from '../lib/api';
import { badge, bindPager, btn, card, empty, esc, icon, on, openDialog, pageHeader, pager, toast } from '../lib/ui';

const receipt = (t) => {
  const row = (k, v) => `<div class="flex justify-between gap-4 border-b py-2 text-sm"><span class="text-muted-foreground">${k}</span><span class="text-right font-medium">${esc(v)}</span></div>`;
  return `
    <div class="print-area rounded-lg border p-4">
      <p class="pb-2 text-center font-bold">RekomFilm</p>
      ${t.invoice_number ? row('No. Faktur', t.invoice_number) : ''}${row('No. Order', t.order_number)}${row('Pembeli', t.user.name)}${row('Film', t.movie.title)}
      ${row('Metode', t.payment_method)}${row('Tanggal', t.formatted_paid_at)}${row('Status', 'LUNAS')}
      <div class="flex justify-between pt-3 font-bold"><span>Total</span><span>${esc(t.formatted_amount)}</span></div>
    </div>
    <button onclick="print()" class="${btn('outline')} mt-4 w-full">${icon('printer')} Cetak / Simpan PDF</button>`;
};

export default async function (root) {
  const load = async (page = 1) => {
    root.innerHTML = pageHeader('Transaksi', 'Riwayat pembelian film dan bukti pembayaran.') + '<div id="rows" class="grid gap-3"></div><div id="pager"></div>';
    try {
      const res = await api.get('/me/transactions', { page });
      root.querySelector('#rows').innerHTML = res.data.length
        ? res.data.map((t) => `
          <div class="${card} flex items-center justify-between gap-3 p-4">
            <div class="min-w-0"><p class="truncate font-semibold">${esc(t.movie.title)}</p>
              <p class="text-xs text-muted-foreground">${esc(t.order_number)} · ${esc(t.payment_method)}</p>
              <p class="text-xs text-muted-foreground">${esc(t.formatted_paid_at)}</p></div>
            <div class="flex shrink-0 items-center gap-2">
              <div class="grid justify-items-end gap-1"><p class="font-bold text-emerald-600 dark:text-emerald-400">${esc(t.formatted_amount)}</p>${badge('Lunas', 'success')}</div>
              <button data-trx="${t.id}" class="${btn('outline', 'icon')}" aria-label="Lihat struk">${icon('receipt')}</button>
            </div>
          </div>`).join('')
        : empty('Belum ada transaksi', 'Film berbayar yang Anda beli akan muncul di sini.');
      root.querySelector('#pager').innerHTML = pager(res);
    } catch (e) { toast(e.message, 'error'); }
  };

  bindPager(root, load);
  on(root, 'click', '[data-trx]', async (e, el) => {
    try {
      const { data } = await api.get(`/transactions/${el.dataset.trx}`);
      openDialog({ title: 'Bukti Pembayaran', description: 'Struk pembelian film', body: receipt(data) });
    } catch (err) { toast(err.message, 'error'); }
  });
  load();
}
