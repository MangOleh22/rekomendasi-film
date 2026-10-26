// Admin: riwayat pembelian seluruh pengguna (cari + filter metode + total pendapatan).
import { api } from '../lib/api';
import { $, badge, bindPager, card, debounce, esc, icon, input, pager, select, tableWrap, td, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

const METHODS = ['QRIS', 'BCA Virtual Account', 'Mandiri Virtual Account'];

export default async function (root) {
  const s = { page: 1, q: '', method: '' };

  root.innerHTML = adminHeader('Riwayat Pembelian') + `
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <div class="relative w-full sm:w-80"><span class="absolute top-2.5 left-2.5 text-muted-foreground">${icon('search')}</span>
        <input id="q" class="${input} pl-8" placeholder="Cari pengguna, film, no. faktur / order…"></div>
      <select id="method" class="${select} w-full sm:w-56"><option value="">Semua metode</option>${METHODS.map((m) => `<option>${m}</option>`).join('')}</select>
      <div id="revenue" class="${card} ml-auto px-4 py-2 text-sm text-muted-foreground"></div>
    </div>
    <div id="rows"></div>`;

  const load = async () => {
    try {
      const res = await api.get('/admin/transactions', { page: s.page, q: s.q, method: s.method });
      $('#revenue', root).innerHTML = `Total pendapatan (sesuai filter): <b class="text-foreground">${esc(res.summary.formatted_revenue)}</b>`;
      $('#rows', root).innerHTML = (res.data.length ? tableWrap(['Waktu', 'Faktur / Order', 'Pengguna', 'Film', 'Metode', 'Total', 'Status'], res.data.map((t) => `<tr>
        ${td(esc(t.formatted_paid_at), 'whitespace-nowrap text-muted-foreground')}
        ${td(`<div class="whitespace-nowrap">${t.invoice_number ? `<b>${esc(t.invoice_number)}</b><br>` : ''}<span class="text-xs text-muted-foreground">${esc(t.order_number)}</span></div>`)}
        ${td(`<b>${esc(t.user?.username ?? '—')}</b><br><span class="text-xs text-muted-foreground">${esc(t.user?.name ?? '')}</span>`)}
        ${td(esc(t.movie?.title ?? '—'))}${td(esc(t.payment_method), 'whitespace-nowrap')}
        ${td(`<b>${esc(t.formatted_amount)}</b>`, 'whitespace-nowrap')}${td(badge(t.status === 'success' ? 'Lunas' : esc(t.status), t.status === 'success' ? 'success' : 'warning'))}</tr>`).join(''))
        : `<div class="${card} p-10 text-center text-sm text-muted-foreground">Belum ada transaksi yang cocok.</div>`) + pager(res);
    } catch (e) { toast(e.message, 'error'); }
  };

  $('#q', root).oninput = debounce((e) => { s.q = e.target.value.trim(); s.page = 1; load(); });
  $('#method', root).onchange = (e) => { s.method = e.target.value; s.page = 1; load(); };
  bindPager(root, (p) => { s.page = p; load(); });
  load();
}
