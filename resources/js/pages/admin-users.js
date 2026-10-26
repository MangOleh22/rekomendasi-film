import { api } from '../lib/api';
import { $, badge, bindPager, btn, confirmDialog, esc, formValues, iconBtn, input, label, on, openDialog, pager, select, tableWrap, td, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

const field = (text, control) => `<div class="grid gap-2"><label class="${label}">${text}</label>${control}</div>`;

export default async function (root, me) {
  let users = {};

  const load = async (page = 1) => {
    const res = await api.get('/admin/users', { page });
    users = Object.fromEntries(res.data.map((u) => [u.id, u]));
    root.innerHTML = adminHeader('Pengguna') + tableWrap(['Username', 'Nama|max-sm:hidden', 'Lahir|max-md:hidden', 'Min. rating|max-md:hidden', 'Peran|max-sm:hidden', 'Status', 'Aksi'], res.data.map((u) => {
      const self = u.id === me.id; // admin tidak bisa menghapus/menonaktifkan/menurunkan dirinya sendiri
      const lock = self ? 'disabled' : '';
      return `<tr>
        ${td(`<b>${esc(u.username)}</b>${self ? ' ' + badge('Anda', 'outline') : ''}`)}${td(esc(u.name), 'max-sm:hidden')}${td(esc(String(u.birth_date).slice(0, 10)), 'max-md:hidden')}
        ${td(badge(u.minimum_rating, 'outline'), 'max-md:hidden')}${td(badge(u.role === 'admin' ? 'Admin' : 'User', u.role === 'admin' ? 'default' : 'secondary'), 'max-sm:hidden')}
        ${td(badge(u.is_active ? 'Aktif' : 'Nonaktif', u.is_active ? 'success' : 'destructive'))}
        ${td(`<div class="flex gap-0.5">
          ${iconBtn('trash', 'Hapus', `data-act="delete" data-id="${u.id}" ${lock}`, 'text-destructive hover:text-destructive')}
          ${iconBtn('pencil', 'Edit', `data-act="edit" data-id="${u.id}"`)}
          ${u.is_active ? iconBtn('ban', 'Nonaktifkan', `data-act="disable" data-id="${u.id}" ${lock}`) : iconBtn('userCheck', 'Aktifkan', `data-act="enable" data-id="${u.id}"`)}
          ${iconBtn('key', 'Ubah izin', `data-act="role" data-id="${u.id}" ${lock}`)}
        </div>`)}</tr>`;
    }).join('')) + pager(res);
  };

  const save = async (id, body, okMsg) => {
    try { toast((await api.put(`/admin/users/${id}`, body)).message || okMsg); await load(); } catch (err) { toast(err.message, 'error'); }
  };

  const actions = {
    async delete(u) {
      if (!(await confirmDialog(`Hapus pengguna "${u.username}"? Riwayat tonton, transaksi, dan keranjangnya ikut terhapus.`, { confirmText: 'Hapus' }))) return;
      try { toast((await api.del(`/admin/users/${u.id}`)).message); await load(); } catch (err) { toast(err.message, 'error'); }
    },
    edit(u) {
      const dlg = openDialog({
        title: 'Edit pengguna', description: `@${esc(u.username)}`,
        body: `<form class="grid gap-4">
          ${field('Nama', `<input name="name" class="${input}" value="${esc(u.name)}" required>`)}
          ${field('Tanggal lahir', `<input name="birth_date" type="date" class="${input}" value="${esc(String(u.birth_date).slice(0, 10))}" required>`)}
          ${field('Rating minimum (0–10)', `<input name="minimum_rating" type="number" min="0" max="10" step="0.1" class="${input}" value="${u.minimum_rating}" required>`)}
          <button class="${btn()}">Simpan</button></form>`,
      });
      $('form', dlg.el).onsubmit = async (ev) => { ev.preventDefault(); dlg.close(); await save(u.id, formValues(ev.target), 'Pengguna diperbarui.'); };
    },
    async disable(u) {
      if (await confirmDialog(`Nonaktifkan "${u.username}"? Pengguna ini langsung keluar dan tidak bisa masuk lagi.`, { confirmText: 'Nonaktifkan' })) await save(u.id, { is_active: false });
    },
    enable: (u) => save(u.id, { is_active: true }),
    role(u) {
      const dlg = openDialog({
        title: 'Ubah izin', description: `Peran untuk @${esc(u.username)}`,
        body: `<form class="grid gap-4">
          ${field('Peran', `<select name="role" class="${select}"><option value="user" ${u.role === 'user' ? 'selected' : ''}>User — hanya menonton & membeli film</option>
            <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin — dapat mengelola film, genre, pengguna, dan transaksi</option></select>`)}
          <button class="${btn()}">Simpan izin</button></form>`,
      });
      $('form', dlg.el).onsubmit = async (ev) => { ev.preventDefault(); dlg.close(); await save(u.id, formValues(ev.target), 'Izin diperbarui.'); };
    },
  };

  bindPager(root, load);
  on(root, 'click', '[data-act]', (e, el) => actions[el.dataset.act](users[el.dataset.id]));
  load();
}
