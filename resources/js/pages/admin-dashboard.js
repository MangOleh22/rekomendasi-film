import { api, download } from '../lib/api';
import { btn, card, esc, icon, on, rupiah, select, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

const STATS = [
  ['total_movies', 'Total Film', 'film'], ['total_genres', 'Total Genre', 'tag'], ['total_users', 'Pengguna', 'users'],
  ['total_watched', 'Total Tontonan', 'eye'], ['total_transactions', 'Total Transaksi', 'receipt'], ['total_revenue', 'Total Pendapatan', 'card', rupiah],
];

export default async function (root) {
  const [{ data }, genres] = await Promise.all([api.get('/admin/stats'), api.get('/genres')]);
  const list = genres.data || genres;
  root.innerHTML = adminHeader('Panel Admin') + `<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">${STATS.map(([k, text, ic, fmt]) => `
    <div class="${card} grid gap-2 p-5"><div class="flex items-center justify-between text-sm text-muted-foreground">${text}${icon(ic)}</div>
      <p class="text-3xl font-semibold">${fmt ? fmt(data[k]) : data[k]}</p></div>`).join('')}</div>
    <div class="${card} mt-6 grid gap-3 p-5">
      <div><h2 class="font-semibold">Dataset Machine Learning (CSV)</h2>
        <p class="text-sm text-muted-foreground">Pengguna yang pernah menonton film bergenre terpilih beserta genre favorit, usia, dan jumlah tontonan per genre. Data dianonimkan.</p></div>
      <div class="flex flex-wrap gap-2">
        <select data-ml-genre class="${select} w-auto">${list.map((g) => `<option value="${esc(g.slug)}" ${g.slug === 'thriller' ? 'selected' : ''}>${esc(g.name)}</option>`).join('')}</select>
        <button data-ml-export class="${btn()}">${icon('download')} Unduh CSV</button>
      </div></div>`;

  on(root, 'click', '[data-ml-export]', async (e, el) => {
    el.disabled = true;
    try {
      const genre = root.querySelector('[data-ml-genre]').value;
      await download(`/admin/export/ml?genre=${encodeURIComponent(genre)}`, `dataset-ml-${genre}.csv`);
      toast('CSV berhasil diunduh.');
    } catch (err) { toast(err.message, 'error'); } finally { el.disabled = false; }
  });
}
