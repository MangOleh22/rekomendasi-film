// Entry point. Blade (resources/views/app.blade.php) hanya memuat <body data-page="...">;
// file ini menjaga akses (login/admin), menggambar kerangka navigasi, lalu memuat modul di ./pages/<nama>.js
import '../css/app.css';
import { api, getToken, setToken } from './lib/api';
import { toggleTheme } from './lib/theme';
import { $, btn, esc, icon, themeToggle, toast } from './lib/ui';

const pages = import.meta.glob('./pages/*.js');
const GUEST = ['login', 'register'];
const name = document.body.dataset.page;

const NAV = [
  ['/', 'Beranda', 'home'],
  ['/history', 'Riwayat', 'history'],
  ['/transactions', 'Transaksi', 'receipt'],
  ['/preferences', 'Preferensi', 'sliders'],
];

// Tombol ganti tema (desktop & mobile memakai atribut yang sama)
document.addEventListener('click', (e) => e.target.closest('[data-theme-toggle]') && toggleTheme());

/** Perbarui angka di ikon keranjang. Dipanggil ulang lewat event "cart:changed". */
async function refreshCartBadge() {
  const badge = $('#cart-badge');
  if (!badge) return;
  try {
    const { count } = await api.get('/cart');
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.toggle('hidden', count === 0);
  } catch { /* abaikan */ }
}

function shell(user) {
  const links = [...NAV, ...(user.role === 'admin' ? [['/admin', 'Admin', 'shield']] : [])];
  const active = (href) => (href === '/' ? location.pathname === '/' : location.pathname.startsWith(href));
  const link = (cls, big) => ([href, text, ic]) =>
    `<a href="${href}" class="${cls} ${active(href) ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'}">${icon(ic, big ? 'size-5' : 'size-4')}${text}</a>`;

  // Tata letak header:
  //  - Desktop (lg+): [logo] [tombol tema] ........ [menu] ........ [nama] [keranjang] [keluar]
  //  - Mobile/tablet : [logo] ........ [tombol tema] [nama] [keranjang] [keluar]  (menu pindah ke bar bawah)
  document.body.innerHTML = `
    <header class="safe-top sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
      <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
        <div class="flex items-center gap-4">
          <a href="/" class="flex items-center gap-2 font-semibold">${icon('film', 'size-5 text-primary')} RekomFilm</a>
          ${themeToggle('max-lg:hidden')}
        </div>
        <nav class="hidden items-center gap-6 text-sm font-medium lg:flex">${links.map(link('flex items-center gap-2 transition-colors')).join('')}</nav>
        <div class="flex items-center gap-3">
          ${themeToggle('lg:hidden')}
          <span class="hidden max-w-32 truncate text-sm text-muted-foreground sm:block">${esc(user.name)}</span>
          <a href="/cart" class="${btn('ghost', 'icon')} relative" title="Keranjang" aria-label="Keranjang">
            ${icon('cart')}<span id="cart-badge" class="absolute -top-1 -right-1 hidden min-w-4 rounded-full bg-primary px-1 text-center text-[10px] leading-4 font-semibold text-primary-foreground"></span>
          </a>
          <button id="logout" class="${btn('ghost', 'icon')}" title="Keluar" aria-label="Keluar">${icon('logout')}</button>
        </div>
      </div>
    </header>
    <main id="page" class="mx-auto max-w-6xl px-4 py-6 pb-28 lg:pb-10"></main>
    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 backdrop-blur lg:hidden">
      <div class="grid" style="grid-template-columns:repeat(${links.length},minmax(0,1fr))">${links.map(link('flex flex-col items-center gap-1 py-2 text-[11px] font-medium', true)).join('')}</div>
    </nav>`;

  $('#logout').onclick = async () => { await api.post('/logout').catch(() => {}); setToken(null); location.href = '/login'; };
  window.addEventListener('cart:changed', refreshCartBadge);
  refreshCartBadge();
}

async function boot() {
  let user = null;
  if (getToken()) {
    try { user = (await api.get('/me')).data; } catch { setToken(null); }
  }

  if (GUEST.includes(name)) {
    if (user) return (location.href = '/');
  } else {
    if (!user) return (location.href = '/login');
    if (name.startsWith('admin') && user.role !== 'admin') return (location.href = '/');
    shell(user);
  }

  const mod = await pages[`./pages/${name}.js`]();
  await mod.default($('#page') || document.body, user);
}

boot().catch((e) => toast(e.message, 'error'));

// PWA: daftarkan service worker (public/sw.js)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
