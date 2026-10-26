import { api } from '../lib/api';
import { ageBadge, bindPager, card, empty, esc, pageHeader, pager, poster, toast } from '../lib/ui';

export default async function (root) {
  const load = async (page = 1) => {
    root.innerHTML = pageHeader('Riwayat Tonton', 'Film yang pernah Anda tonton memengaruhi rekomendasi berikutnya.') + '<div id="rows" class="grid gap-3"></div><div id="pager"></div>';
    try {
      const res = await api.get('/me/watch-history', { page });
      root.querySelector('#rows').innerHTML = res.data.length
        ? res.data.map((h) => `
          <div class="${card} flex items-center gap-4 p-3">
            <div class="h-20 w-14 shrink-0 overflow-hidden rounded-md">${poster(h.movie)}</div>
            <div class="min-w-0 flex-1">
              <p class="truncate font-semibold">${esc(h.movie.title)}</p>
              <p class="truncate text-xs text-muted-foreground">${h.movie.genres.map((g) => esc(g.name)).join(' · ')}</p>
              <p class="mt-1 text-xs text-muted-foreground">${new Date(h.watched_at).toLocaleString('id-ID')}</p>
            </div>${ageBadge(h.movie)}
          </div>`).join('')
        : empty('Belum ada riwayat', 'Tonton film dari beranda untuk mulai membangun riwayat.');
      root.querySelector('#pager').innerHTML = pager(res);
    } catch (e) { toast(e.message, 'error'); }
  };
  bindPager(root, load);
  load();
}
