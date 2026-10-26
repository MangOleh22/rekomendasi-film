import { api } from '../lib/api';
import { btn, card, chip, input, label, pageHeader, toast, $ } from '../lib/ui';

export default async function (root, user) {
  const genres = (await api.get('/genres')).data;
  const favs = user.favorite_genres || [];

  root.innerHTML = `
    <form id="form" class="mx-auto grid max-w-2xl gap-6">
      ${pageHeader('Preferensi', 'Atur profil dan genre favorit untuk rekomendasi yang lebih tepat.')}
      <section class="${card} grid gap-4 p-5">
        <div><h2 class="font-semibold">Profil</h2><p class="text-sm text-muted-foreground">Tanggal lahir menentukan film yang boleh Anda akses.</p></div>
        <div class="grid gap-2"><label class="${label}" for="name">Nama</label><input id="name" name="name" class="${input}" value="${user.name.replace(/"/g, '&quot;')}" required></div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="grid gap-2"><label class="${label}" for="bd">Tanggal lahir</label><input id="bd" type="date" name="birth_date" class="${input}" value="${user.birth_date || ''}" required></div>
          <div class="grid gap-2"><label class="${label}" for="mr">Rating minimum (0–10)</label><input id="mr" type="number" name="minimum_rating" min="0" max="10" step="0.1" class="${input}" value="${user.minimum_rating}" required></div>
        </div>
      </section>
      <section class="${card} grid gap-4 p-5">
        <div><h2 class="font-semibold">Genre favorit</h2><p class="text-sm text-muted-foreground">Dipakai algoritma rekomendasi (afinitas genre).</p></div>
        <div class="flex flex-wrap gap-2">${genres.map((g) => chip('genre', g.id, g.name, favs.includes(g.id))).join('')}</div>
      </section>
      <button class="${btn()}">Simpan perubahan</button>
    </form>`;

  $('#form', root).onsubmit = async (e) => {
    e.preventDefault();
    const b = $('button', e.target);
    b.disabled = true;
    try {
      await api.put('/me', { name: e.target.elements.name.value, birth_date: e.target.elements.birth_date.value, minimum_rating: e.target.elements.minimum_rating.value });
      const ids = [...e.target.querySelectorAll('[name=genre]:checked')].map((c) => Number(c.value));
      await api.put('/me/favorite-genres', { genre_ids: ids });
      toast('Preferensi disimpan.');
    } catch (err) { toast(err.message, 'error'); }
    b.disabled = false;
  };
}
