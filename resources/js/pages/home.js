// Beranda: tab "Untuk Anda" (rekomendasi) & "Katalog" (filter genre + paginasi) + pencarian.
import { api } from '../lib/api';
import { $, $$, badge, bindPager, btn, card, debounce, esc, icon, input, movieGrid, on, pager, skeletonGrid, toast } from '../lib/ui';
import { listen } from '../lib/realtime';
import { showMovie } from './movie-dialog';

export default async function (root, user) {
  const s = { tab: 'recommend', genre: '', page: 1, q: '' };
  const genres = (await api.get('/genres').catch(() => ({ data: [] }))).data;

  const tab = (id, ic, text) =>
    `<button data-tab="${id}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-sm font-medium transition-all [&_svg]:size-4">${icon(ic)}${text}</button>`;

  root.innerHTML = `
    <section class="${card} mb-6 bg-gradient-to-br from-primary/15 via-card to-card p-5">
      <h1 class="text-2xl font-bold tracking-tight">Halo, ${esc(user.name)}</h1>
      <p class="mt-1 text-sm text-muted-foreground">Usia ${user.age} tahun · hanya film yang sesuai klasifikasi LSF yang ditampilkan.</p>
      <div class="mt-3 flex flex-wrap items-center gap-2">${badge(`Rating min ${user.minimum_rating}`, 'outline')}
        <a href="/preferences" class="${btn('secondary', 'sm')}">Atur preferensi</a></div>
    </section>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div id="tabs" class="inline-flex h-9 items-center rounded-lg bg-muted p-[3px] text-muted-foreground">${tab('recommend', 'sparkles', 'Untuk Anda')}${tab('catalog', 'film', 'Katalog')}</div>
      <div class="relative w-full sm:w-72">
        <span class="absolute top-2.5 left-2.5 text-muted-foreground">${icon('search')}</span>
        <input id="q" class="${input} pl-8" placeholder="Cari judul atau sinopsis…">
      </div>
    </div>
    <div id="genres" class="-mx-4 mb-4 hidden gap-2 overflow-x-auto px-4 pb-1"></div>
    <div id="list"></div><div id="pager"></div>`;

  const list = $('#list', root);

  function paintControls() {
    $$('[data-tab]', root).forEach((b) => {
      const on_ = b.dataset.tab === s.tab;
      b.classList.toggle('bg-background', on_); b.classList.toggle('text-foreground', on_); b.classList.toggle('shadow-sm', on_);
    });
    const gbox = $('#genres', root);
    gbox.classList.toggle('hidden', s.tab !== 'catalog' || !!s.q);
    gbox.classList.toggle('flex', s.tab === 'catalog' && !s.q);
    gbox.innerHTML = [{ slug: '', name: 'Semua' }, ...genres]
      .map((g) => `<button data-genre="${esc(g.slug)}" class="${btn(g.slug === s.genre ? 'default' : 'outline', 'sm')}">${esc(g.name)}</button>`).join('');
  }

  let seq = 0; // nomor permintaan terakhir: respons yang lebih lama diabaikan agar tidak menimpa daftar terbaru
  async function load() {
    const mine = ++seq;
    paintControls();
    list.innerHTML = skeletonGrid();
    $('#pager', root).innerHTML = '';
    try {
      let res;
      if (s.q) res = await api.get('/movies/search', { q: s.q });
      else if (s.tab === 'recommend') res = await api.get('/recommendations');
      else res = await api.get('/movies', { genre: s.genre, page: s.page });
      if (mine !== seq) return;
      list.innerHTML = movieGrid(res.data, 'Coba ubah kata kunci, genre, atau preferensi rating Anda.');
      if (!s.q && s.tab === 'catalog') $('#pager', root).innerHTML = pager(res);
    } catch (e) { if (mine !== seq) return; toast(e.message, 'error'); list.innerHTML = ''; }
  }

  on(root, 'click', '[data-tab]', (e, el) => { s.tab = el.dataset.tab; s.page = 1; load(); });
  on(root, 'click', '[data-genre]', (e, el) => { s.genre = el.dataset.genre; s.page = 1; load(); });
  bindPager(root, (p) => { s.page = p; load(); });
  $('#q', root).oninput = debounce((e) => { s.q = e.target.value.trim(); s.page = 1; load(); });

  const open = (el) => showMovie(el.dataset.movie, load);
  on(list, 'click', '[data-movie]', (e, el) => open(el));
  on(list, 'keydown', '[data-movie]', (e, el) => e.key === 'Enter' && open(el));

  load();

  // Realtime: admin mengubah katalog -> beri tahu & muat ulang daftar
  const verbs = { created: 'Ada film baru di katalog.', updated: 'Katalog film diperbarui.', deleted: 'Sebuah film dihapus dari katalog.' };
  listen('movies', 'movie.changed', (e) => { toast(verbs[e.action] || 'Katalog film berubah.'); load(); });
}
