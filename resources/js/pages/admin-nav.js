import { pageHeader } from '../lib/ui';

const TABS = [['/admin', 'Ringkasan'], ['/admin/movies', 'Film'], ['/admin/genres', 'Genre'], ['/admin/users', 'Pengguna'], ['/admin/transactions', 'Riwayat Pembelian']];

/** Judul halaman admin + tab antar halaman admin. */
export const adminHeader = (title, actions = '') => `${pageHeader(title, '', actions)}
  <div class="-mt-2 mb-6 flex gap-1 overflow-x-auto overflow-y-hidden border-b">${TABS.map(([href, text]) =>
    `<a href="${href}" class="-mb-px border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap ${location.pathname === href ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'}">${text}</a>`).join('')}</div>`;
