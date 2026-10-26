// Mode gelap/terang. Pilihan disimpan di localStorage; kelas "dark" dipasang di <html>
// (kelas awal dipasang oleh skrip kecil di <head> app.blade.php agar tidak berkedip).
const KEY = 'theme';

export const getTheme = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

/** Ganti tema dengan transisi halus (dilewati jika pengguna memilih "kurangi gerakan"). */
export function setTheme(theme) {
  const root = document.documentElement;
  if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
    root.classList.add('theme-anim');
    setTimeout(() => root.classList.remove('theme-anim'), 600);
  }
  root.classList.toggle('dark', theme === 'dark');
  try { localStorage.setItem(KEY, theme); } catch { /* mode privat */ }
  document.querySelector('meta[name=theme-color]')?.setAttribute('content', theme === 'dark' ? '#09090b' : '#ffffff');
}

export const toggleTheme = () => setTheme(getTheme() === 'dark' ? 'light' : 'dark');
