// Dipakai oleh halaman login & register (lihat login.js / register.js).
import { api, setToken } from '../lib/api';
import { $, btn, card, formValues, icon, input, label, themeToggle, toast } from '../lib/ui';

export function authPage(root, { title, desc, fields, submitText, endpoint, footer }) {
  document.body.innerHTML = `
    <div class="safe-top fixed top-3 left-3">${themeToggle()}</div>
    <div class="safe-top grid min-h-dvh place-items-center p-4"><div class="w-full max-w-sm">
      <div class="mb-6 flex items-center justify-center gap-2 text-xl font-bold">${icon('film', 'size-6 text-primary')} RekomFilm</div>
      <form id="form" class="${card} grid gap-4 p-6">
        <div class="grid gap-1.5"><h1 class="text-lg leading-none font-semibold">${title}</h1><p class="text-sm text-muted-foreground">${desc}</p></div>
        ${fields.map(([n, text, type, ac]) => `<div class="grid gap-2"><label for="${n}" class="${label}">${text}</label>
          <input id="${n}" name="${n}" type="${type}" autocomplete="${ac}" required class="${input}"></div>`).join('')}
        <button class="${btn()}">${submitText}</button>
      </form>
      <p class="mt-4 text-center text-sm text-muted-foreground">${footer}</p>
    </div></div>`;

  $('#form').onsubmit = async (e) => {
    e.preventDefault();
    const b = $('button', e.target);
    b.disabled = true;
    try {
      setToken((await api.post(endpoint, formValues(e.target))).token);
      location.href = '/';
    } catch (err) { toast(err.message, 'error'); b.disabled = false; }
  };
}
