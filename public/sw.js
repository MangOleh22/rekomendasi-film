// Service worker RekomFilm. Strategi:
//  - /api/*            : tidak pernah di-cache (data selalu segar, aman untuk token)
//  - /build/* (aset)   : cache-first (nama file ber-hash, aman di-cache lama)
//  - navigasi halaman  : network-first, jatuh ke shell SPA ter-cache lalu offline.html
// Naikkan VERSION untuk membersihkan cache lama.
const VERSION = 'v3';
const CACHE = `rekomfilm-${VERSION}`;
const PRECACHE = ['/offline.html', '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (e) => {
  const { request } = e;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== location.origin || url.pathname.startsWith('/api')) return;

  const save = (res) => {
    if (res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(request, copy)); }
    return res;
  };

  if (request.mode === 'navigate') {
    e.respondWith(
      fetch(request).then(save).catch(() => caches.match(request).then((r) => r || caches.match('/offline.html'))),
    );
    return;
  }

  // Aset statis & poster: cache-first
  e.respondWith(caches.match(request).then((hit) => hit || fetch(request).then(save)));
});
