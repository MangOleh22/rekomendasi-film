import { api } from '../lib/api';
import { card, icon, rupiah } from '../lib/ui';
import { adminHeader } from './admin-nav';

const STATS = [
  ['total_movies', 'Total Film', 'film'], ['total_genres', 'Total Genre', 'tag'], ['total_users', 'Pengguna', 'users'],
  ['total_watched', 'Total Tontonan', 'eye'], ['total_transactions', 'Total Transaksi', 'receipt'], ['total_revenue', 'Total Pendapatan', 'card', rupiah],
];

export default async function (root) {
  const { data } = await api.get('/admin/stats');
  root.innerHTML = adminHeader('Panel Admin') + `<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">${STATS.map(([k, text, ic, fmt]) => `
    <div class="${card} grid gap-2 p-5"><div class="flex items-center justify-between text-sm text-muted-foreground">${text}${icon(ic)}</div>
      <p class="text-3xl font-semibold">${fmt ? fmt(data[k]) : data[k]}</p></div>`).join('')}</div>`;
}
