import { api, download } from '../lib/api';
import { badge, btn, card, icon, on, rupiah, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

const STATS = [
  ['total_movies', 'Total Film', 'film'], ['total_genres', 'Total Genre', 'tag'], ['total_users', 'Pengguna', 'users'],
  ['total_watched', 'Total Tontonan', 'eye'], ['total_transactions', 'Total Transaksi', 'receipt'], ['total_revenue', 'Total Pendapatan', 'card', rupiah],
];

const COLUMNS = ['user_id', 'age', 'age_category', 'minimum_rating', 'total_watch', 'top_genre', 'fav_<genre>', 'watch_<genre>', 'favorite_genres'];

export default async function (root) {
  const [{ data }, { data: ml }] = await Promise.all([api.get('/admin/stats'), api.get('/admin/export/ml/summary')]);
  root.innerHTML = adminHeader('Panel Admin') + `<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">${STATS.map(([k, text, ic, fmt]) => `
    <div class="${card} grid gap-2 p-5"><div class="flex items-center justify-between text-sm text-muted-foreground">${text}${icon(ic)}</div>
      <p class="text-3xl font-semibold">${fmt ? fmt(data[k]) : data[k]}</p></div>`).join('')}</div>

    <section class="${card} mt-6 overflow-hidden">
      <div class="flex flex-col gap-5 bg-gradient-to-br from-primary/15 via-transparent to-transparent p-6 sm:flex-row sm:items-center">
        <div class="grid size-14 shrink-0 place-items-center rounded-xl bg-primary/15 text-primary">${icon('sparkles', 'size-7')}</div>
        <div class="grid flex-1 gap-1">
          <h2 class="text-lg font-semibold">Dataset Machine Learning</h2>
          <p class="text-sm text-muted-foreground">Preferensi tontonan semua pengguna untuk semua genre dalam satu file CSV: usia, genre favorit, jumlah tontonan per genre, dan genre yang paling sering ditonton. Data dianonimkan dan siap dibuka di Excel, Google Sheets, atau Python (pandas).</p>
        </div>
        <button data-ml-export class="${btn()} sm:self-center">${icon('download')} Unduh CSV</button>
      </div>
      <div class="grid gap-4 border-t p-6 sm:grid-cols-3">
        <div><p class="text-2xl font-semibold">${ml.rows}</p><p class="text-xs text-muted-foreground">Baris (pengguna dengan riwayat tonton)</p></div>
        <div><p class="text-2xl font-semibold">${ml.genres}</p><p class="text-xs text-muted-foreground">Genre dalam dataset</p></div>
        <div><p class="text-2xl font-semibold">${6 + ml.genres * 2 + 1}</p><p class="text-xs text-muted-foreground">Kolom fitur</p></div>
      </div>
      <div class="flex flex-wrap gap-1.5 border-t p-6 pt-4">${COLUMNS.map((c) => badge(c, 'outline')).join('')}</div>
    </section>`;

  on(root, 'click', '[data-ml-export]', async (e, el) => {
    el.disabled = true;
    try {
      await download('/admin/export/ml', 'dataset-ml-rekomfilm.csv');
      toast('Dataset CSV berhasil diunduh.');
    } catch (err) { toast(err.message, 'error'); } finally { el.disabled = false; }
  });
}
