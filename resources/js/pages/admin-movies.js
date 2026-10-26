import { api } from '../lib/api';
import { $, confirmDialog, ageBadge, badge, bindPager, btn, chip, debounce, esc, icon, input, label, on, openDialog, pager, select, tableWrap, td, textarea, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

const AGES = [[0, 'SU (Semua Usia)'], [13, '13+'], [17, '17+'], [21, '21+']];

const field = (text, control) => `<div class="grid gap-2"><label class="${label}">${text}</label>${control}</div>`;

const form = (m, genres) => `
  <form class="grid gap-4">
    ${field('Judul', `<input name="title" class="${input}" value="${esc(m?.title)}" required>`)}
    ${field('Sinopsis', `<textarea name="synopsis" rows="3" class="${textarea}">${esc(m?.synopsis)}</textarea>`)}
    <div class="grid grid-cols-2 gap-4">
      ${field('Tahun', `<input name="year" type="number" class="${input}" value="${m?.year ?? new Date().getFullYear()}" required>`)}
      ${field('Rating (0–10)', `<input name="rating" type="number" step="0.1" min="0" max="10" class="${input}" value="${m?.rating ?? 7}" required>`)}
      ${field('Harga (Rp)', `<input name="price" type="number" min="0" class="${input}" value="${m?.price ?? 0}">`)}
      ${field('Klasifikasi usia', `<select name="age_limit" class="${select}">${AGES.map(([v, t]) => `<option value="${v}" ${Number(m?.age_limit ?? 0) === v ? 'selected' : ''}>${t}</option>`).join('')}</select>`)}
    </div>
    ${field('Genre', `<div class="flex flex-wrap gap-2">${genres.map((g) => chip('genres[]', g.id, g.name, m?.genres.some((x) => x.id === g.id))).join('')}</div>`)}
    ${field('Poster (ukuran bebas)', `<input name="poster" type="file" accept="image/*" class="${input} pt-1.5">`)}
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" class="size-4 accent-[var(--primary)]" ${m?.is_active !== false ? 'checked' : ''}> Tampilkan di katalog</label>
    <button class="${btn()}">Simpan</button>
  </form>`;

export default async function (root) {
  const s = { page: 1, q: '' };
  const genres = (await api.get('/genres')).data;

  const load = async () => {
    const res = await api.get('/admin/movies', { page: s.page, q: s.q });
    $('#rows', root).innerHTML = tableWrap(['Judul', 'Usia', 'Rating', 'Harga', 'Status', ''], res.data.map((m) => `<tr>
      ${td(`<b>${esc(m.title)}</b> <span class="text-muted-foreground">(${m.year})</span>`, 'max-w-56 truncate')}${td(ageBadge(m))}${td(Number(m.rating).toFixed(1))}${td(esc(m.formatted_price))}
      ${td(badge(m.is_active ? 'Aktif' : 'Nonaktif', m.is_active ? 'success' : 'secondary'))}
      ${td(`<button data-edit="${m.id}" class="${btn('ghost', 'icon')}" aria-label="Ubah">${icon('pencil')}</button>
            <button data-del="${m.id}" data-title="${esc(m.title)}" class="${btn('ghost', 'icon')}" aria-label="Hapus">${icon('trash')}</button>`, 'text-right whitespace-nowrap')}</tr>`).join(''))
      + pager(res);
    root.movies = Object.fromEntries(res.data.map((m) => [m.id, m]));
  };

  root.innerHTML = adminHeader('Film', `<button data-edit class="${btn()}">${icon('plus')} Tambah</button>`) + `
    <div class="relative mb-4 max-w-sm"><span class="absolute top-2.5 left-2.5 text-muted-foreground">${icon('search')}</span>
      <input id="q" class="${input} pl-8" placeholder="Cari judul…"></div><div id="rows"></div>`;

  $('#q', root).oninput = debounce((e) => { s.q = e.target.value; s.page = 1; load(); });
  bindPager(root, (p) => { s.page = p; load(); });

  on(root, 'click', '[data-edit]', (e, el) => {
    const m = root.movies[el.dataset.edit];
    const dlg = openDialog({ title: m ? 'Ubah film' : 'Tambah film', body: form(m, genres), wide: true });
    $('form', dlg.el).onsubmit = async (ev) => {
      ev.preventDefault();
      const fd = new FormData(ev.target);
      fd.set('is_active', ev.target.is_active.checked ? '1' : '0');
      fd.set('genres_sent', '1'); // agar "tanpa genre" juga tersimpan
      if (!fd.get('poster')?.size) fd.delete('poster');
      if (m) fd.append('_method', 'PUT'); // multipart + PUT harus lewat method spoofing
      try {
        toast((await api.post(m ? `/admin/movies/${m.id}` : '/admin/movies', fd)).message);
        dlg.close(); load();
      } catch (err) { toast(err.message, 'error'); }
    };
  });

  on(root, 'click', '[data-del]', async (e, el) => {
    if (!(await confirmDialog(`Hapus film "${el.dataset.title}"?`))) return;
    try { toast((await api.del(`/admin/movies/${el.dataset.del}`)).message); load(); } catch (err) { toast(err.message, 'error'); }
  });

  load();
}
