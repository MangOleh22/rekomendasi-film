// Helper tampilan: class gaya shadcn (Tailwind), ikon, toast, dialog, kartu film.
// Semua fungsi mengembalikan string HTML; gunakan esc() untuk data dari server.

export const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
export const rupiah = (n) => (Number(n) > 0 ? `Rp ${Number(n).toLocaleString('id-ID')}` : 'Gratis');
export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
export const debounce = (fn, ms = 400) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
export const formValues = (form) => Object.fromEntries(new FormData(form));

/** Event delegation: on(root, 'click', '[data-x]', (e, el) => ...) */
export const on = (root, type, sel, fn) =>
  root.addEventListener(type, (e) => { const el = e.target.closest(sel); if (el && root.contains(el)) fn(e, el); });

// ---------- Kelas gaya (komponen shadcn) ----------
const BTN = 'inline-flex shrink-0 items-center justify-center gap-2 rounded-md text-sm font-medium whitespace-nowrap transition-all outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0';
const BTN_VARIANT = {
  default: 'bg-primary text-primary-foreground hover:bg-primary/90',
  secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
  outline: 'border bg-background shadow-xs hover:bg-accent',
  ghost: 'hover:bg-accent',
  destructive: 'bg-destructive text-white hover:bg-destructive/90',
};
const BTN_SIZE = { default: 'h-9 px-4 py-2', sm: 'h-8 px-3', icon: 'size-9' };
export const btn = (variant = 'default', size = 'default') => `${BTN} ${BTN_VARIANT[variant]} ${BTN_SIZE[size]}`;

export const input = 'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';
export const select = `${input} bg-card`;
export const textarea = 'min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';
export const card = 'rounded-xl border bg-card text-card-foreground shadow-sm';
export const label = 'text-sm leading-none font-medium';

const BADGE = {
  default: 'bg-primary text-primary-foreground border-transparent',
  secondary: 'bg-secondary text-secondary-foreground border-transparent',
  outline: '',
  success: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-transparent',
  warning: 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-transparent',
  info: 'bg-sky-500/15 text-sky-700 dark:text-sky-400 border-transparent',
  destructive: 'bg-destructive/15 text-destructive border-transparent',
};
export const badge = (text, variant = 'secondary') =>
  `<span class="inline-flex w-fit items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap ${BADGE[variant]}">${text}</span>`;

export const ageBadge = (m) => {
  const a = Number(m.age_limit);
  return badge(a < 13 ? 'SU' : `${a}+`, a < 13 ? 'success' : a < 17 ? 'info' : a < 21 ? 'warning' : 'destructive');
};

/** Chip pilihan (checkbox bergaya tombol): name, value, label, checked */
export const chip = (name, value, text, checked) => `
  <label class="cursor-pointer">
    <input type="checkbox" name="${name}" value="${value}" ${checked ? 'checked' : ''} class="peer sr-only">
    <span class="${btn('outline', 'sm')} peer-checked:border-transparent peer-checked:bg-primary peer-checked:text-primary-foreground peer-focus-visible:ring-[3px]">${esc(text)}</span>
  </label>`;

// ---------- Ikon (lucide, inline SVG) ----------
const ICONS = {
  film: '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18M3 7.5h4M3 12h18M3 16.5h4M17 3v18M17 7.5h4M17 16.5h4"/>',
  home: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
  history: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>',
  receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8M12 17.5v-11"/>',
  sliders: '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M2 14h4M10 8h4M18 16h4"/>',
  shield: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
  logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
  star: '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',
  search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  sparkles: '<path d="m12 3-1.9 5.8L4 10l6.1 1.2L12 17l1.9-5.8L20 10l-6.1-1.2z"/>',
  x: '<path d="M18 6 6 18M6 6l12 12"/>',
  plus: '<path d="M5 12h14M12 5v14"/>',
  pencil: '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>',
  trash: '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
  play: '<polygon points="6 3 20 12 6 21 6 3"/>',
  card: '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  printer: '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3h12v6M6 14h12v8H6z"/>',
  left: '<path d="m15 18-6-6 6-6"/>',
  right: '<path d="m9 18 6-6-6-6"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  eye: '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2m-7.07-2.93 1.41-1.41m11.32-11.32 1.41-1.41M2 12h2m16 0h2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41"/>',
  moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
  cart: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
  ban: '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
  userCheck: '<path d="m16 11 2 2 4-4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
  key: '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
  tag: '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/>',
};
export const icon = (name, cls = 'size-4') =>
  `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name]}</svg>`;

// ---------- Toast ----------
export function toast(message, type = 'success') {
  let box = $('#toasts');
  if (!box) {
    box = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'toasts', className: 'safe-top pointer-events-none fixed inset-x-0 top-3 z-[100] flex flex-col items-center gap-2 px-4' }));
  }
  const el = Object.assign(document.createElement('div'), {
    className: `pointer-events-auto flex items-center gap-2 rounded-lg border bg-popover px-4 py-3 text-sm shadow-lg animate-in fade-in slide-in-from-top-2 ${type === 'error' ? 'border-destructive text-destructive' : ''}`,
    innerHTML: `${icon(type === 'error' ? 'x' : 'check', `size-4 ${type === 'error' ? '' : 'text-emerald-600 dark:text-emerald-400'}`)}<span>${esc(message)}</span>`,
  });
  box.append(el);
  setTimeout(() => el.remove(), 3500);
}

// ---------- Dialog ----------
/** Buka dialog modal. Mengembalikan {el, close}. Tutup via tombol X, klik latar, atau Escape. */
export function openDialog({ title, description = '', body, wide = false, onClose }) {
  const root = Object.assign(document.createElement('div'), {
    className: 'fixed inset-0 z-50 grid place-items-center bg-black/60 p-4 animate-in fade-in',
    innerHTML: `
      <div role="dialog" aria-modal="true" class="relative grid max-h-[90dvh] w-full ${wide ? 'max-w-2xl' : 'max-w-lg'} gap-4 overflow-y-auto rounded-lg border bg-background p-6 shadow-lg animate-in zoom-in-95">
        <div class="grid gap-1.5 pr-6"><h2 class="text-lg leading-none font-semibold">${esc(title)}</h2>${description ? `<p class="text-sm text-muted-foreground">${description}</p>` : ''}</div>
        <button data-close class="absolute top-4 right-4 opacity-70 hover:opacity-100" aria-label="Tutup">${icon('x')}</button>
        <div data-body>${body}</div>
      </div>`,
  });
  const close = () => { root.remove(); document.removeEventListener('keydown', esc_); onClose?.(); };
  const esc_ = (e) => e.key === 'Escape' && close();
  root.addEventListener('click', (e) => (e.target === root || e.target.closest('[data-close]')) && close());
  document.addEventListener('keydown', esc_);
  document.body.append(root);
  return { el: $('[data-body]', root), root, close };
}

// ---------- Komponen konten ----------
export const skeletonGrid = (n = 10) => `<div class="${GRID}">${'<div class="aspect-[2/3.6] animate-pulse rounded-xl bg-accent"></div>'.repeat(n)}</div>`;
export const GRID = 'grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-4 xl:grid-cols-5';

export const empty = (title, text = '') => `
  <div class="grid place-items-center gap-2 rounded-xl border border-dashed py-16 text-center">
    ${icon('film', 'size-8 text-muted-foreground')}<p class="font-medium">${esc(title)}</p>
    ${text ? `<p class="max-w-sm text-sm text-muted-foreground">${esc(text)}</p>` : ''}
  </div>`;

export const pageHeader = (title, desc = '', actions = '') => `
  <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div><h1 class="text-2xl font-bold tracking-tight">${esc(title)}</h1>${desc ? `<p class="mt-1 text-sm text-muted-foreground">${esc(desc)}</p>` : ''}</div>${actions}
  </div>`;

export const poster = (m) => m.poster_path
  ? `<img src="${esc(m.poster_path)}" alt="${esc(m.title)}" loading="lazy" class="size-full object-cover">`
  : `<div class="grid size-full place-items-center bg-muted text-muted-foreground">${icon('film', 'size-10')}</div>`;

export const movieCard = (m) => `
  <article data-movie="${m.id}" tabindex="0" role="button" class="${card} group cursor-pointer overflow-hidden transition hover:border-primary/50 hover:shadow-lg">
    <div class="relative aspect-[2/3] overflow-hidden [&_img]:transition [&_img]:duration-300 group-hover:[&_img]:scale-105">
      ${poster(m)}
      <div class="absolute top-2 left-2">${ageBadge(m)}</div>
      ${m.score != null ? `<div class="absolute top-2 right-2">${badge(`${Math.round(m.score)}% cocok`, 'default')}</div>` : ''}
    </div>
    <div class="grid gap-1 p-3">
      <h3 class="line-clamp-1 text-sm font-semibold">${esc(m.title)}</h3>
      <div class="flex items-center gap-2 text-xs text-muted-foreground">
        <span class="flex items-center gap-1 text-amber-500">${icon('star', 'size-3 fill-current')}${Number(m.rating).toFixed(1)}</span>
        <span>${m.year}</span><span class="ml-auto font-medium text-emerald-600 dark:text-emerald-400">${esc(m.formatted_price)}</span>
      </div>
      <p class="line-clamp-1 text-xs text-muted-foreground">${(m.genres || []).map((g) => esc(g.name)).join(' · ')}</p>
    </div>
  </article>`;

export const movieGrid = (movies, emptyText) =>
  movies?.length ? `<div class="${GRID}">${movies.map(movieCard).join('')}</div>` : empty('Film tidak ditemukan', emptyText);

/** Paginasi untuk respons paginate() Laravel. Klik tombol [data-pg] ditangani via bindPager. */
export const pager = (meta) => !meta || meta.last_page <= 1 ? '' : `
  <div class="mt-4 flex items-center justify-end gap-2">
    <span class="text-sm text-muted-foreground">${meta.current_page} / ${meta.last_page}</span>
    <button data-pg="${meta.current_page - 1}" class="${btn('outline', 'icon')}" ${meta.current_page <= 1 ? 'disabled' : ''} aria-label="Sebelumnya">${icon('left')}</button>
    <button data-pg="${meta.current_page + 1}" class="${btn('outline', 'icon')}" ${meta.current_page >= meta.last_page ? 'disabled' : ''} aria-label="Berikutnya">${icon('right')}</button>
  </div>`;
export const bindPager = (root, fn) => on(root, 'click', '[data-pg]', (e, el) => fn(Number(el.dataset.pg)));

/** head: teks kolom; tambahkan "|kelas" untuk kelas Tailwind, mis. 'Lahir|max-md:hidden' menyembunyikan kolom di layar kecil. */
export const tableWrap = (head, rows) => `
  <div class="${card} overflow-x-auto"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-muted-foreground">${head.map((h) => { const [t, c = ''] = h.split('|'); return `<th class="h-10 px-3 font-medium whitespace-nowrap ${c}">${t}</th>`; }).join('')}</tr></thead>
    <tbody>${rows}</tbody></table></div>`;
/** Dialog konfirmasi bergaya shadcn; resolve true jika pengguna menekan tombol konfirmasi. */
export function confirmDialog(message, { confirmText = 'Ya, lanjutkan', danger = true } = {}) {
  return new Promise((resolve) => {
    let answer = false;
    const dlg = openDialog({
      title: 'Konfirmasi',
      description: esc(message),
      onClose: () => resolve(answer),
      body: `<div class="flex justify-end gap-2"><button data-no class="${btn('outline')}">Batal</button>
        <button data-yes class="${btn(danger ? 'destructive' : 'default')}">${esc(confirmText)}</button></div>`,
    });
    on(dlg.el, 'click', '[data-yes]', () => { answer = true; dlg.close(); });
    on(dlg.el, 'click', '[data-no]', () => dlg.close());
  });
}

/** Tombol ikon bergaya shadcn dengan tooltip (title) + aria-label. */
export const iconBtn = (name, text, attrs = '', extra = '') =>
  `<button ${attrs} title="${esc(text)}" aria-label="${esc(text)}" class="${btn('ghost', 'icon')} ${extra}">${icon(name)}</button>`;

/** Tombol ganti tema: ikon matahari/bulan berputar halus. Klik ditangani di main.js ([data-theme-toggle]). */
export const themeToggle = (cls = '') => `
  <button data-theme-toggle title="Ganti mode gelap / terang" aria-label="Ganti mode gelap / terang" class="${btn('outline', 'icon')} relative overflow-hidden ${cls}">
    ${icon('sun', 'absolute size-4 rotate-0 scale-100 transition-all duration-500 dark:-rotate-90 dark:scale-0')}
    ${icon('moon', 'absolute size-4 rotate-90 scale-0 transition-all duration-500 dark:rotate-0 dark:scale-100')}
  </button>`;

export const td = (v, cls = '') => `<td class="border-b p-3 align-middle last:border-0 ${cls}">${v}</td>`;
