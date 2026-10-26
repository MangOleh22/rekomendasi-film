import { api } from '../lib/api';
import { $, confirmDialog, btn, esc, formValues, icon, input, label, on, openDialog, tableWrap, td, toast } from '../lib/ui';
import { adminHeader } from './admin-nav';

export default async function (root) {
  const load = async () => {
    const { data } = await api.get('/genres');
    root.innerHTML = adminHeader('Genre', `<button data-edit class="${btn()}">${icon('plus')} Tambah</button>`) +
      tableWrap(['Nama', 'Slug', 'Film', ''], data.map((g) => `<tr>
        ${td(`<b>${esc(g.name)}</b>`)}${td(esc(g.slug), 'text-muted-foreground')}${td(g.movies_count)}
        ${td(`<button data-edit="${g.id}" data-name="${esc(g.name)}" class="${btn('ghost', 'icon')}" aria-label="Ubah">${icon('pencil')}</button>
              <button data-del="${g.id}" data-name="${esc(g.name)}" class="${btn('ghost', 'icon')}" aria-label="Hapus">${icon('trash')}</button>`, 'text-right whitespace-nowrap')}</tr>`).join(''));
  };

  on(root, 'click', '[data-edit]', (e, el) => {
    const id = el.dataset.edit;
    const dlg = openDialog({
      title: id ? 'Ubah genre' : 'Tambah genre',
      body: `<form class="grid gap-4"><div class="grid gap-2"><label class="${label}">Nama genre</label>
        <input name="name" class="${input}" value="${esc(el.dataset.name || '')}" required></div><button class="${btn()}">Simpan</button></form>`,
    });
    $('form', dlg.el).onsubmit = async (ev) => {
      ev.preventDefault();
      try {
        const body = formValues(ev.target);
        toast((id ? await api.put(`/admin/genres/${id}`, body) : await api.post('/admin/genres', body)).message || 'Tersimpan.');
        dlg.close(); load();
      } catch (err) { toast(err.message, 'error'); }
    };
  });

  on(root, 'click', '[data-del]', async (e, el) => {
    if (!(await confirmDialog(`Hapus genre "${el.dataset.name}"?`))) return;
    try { toast((await api.del(`/admin/genres/${el.dataset.del}`)).message || 'Dihapus.'); load(); } catch (err) { toast(err.message, 'error'); }
  });

  load();
}
