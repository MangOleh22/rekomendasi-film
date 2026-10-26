# Changelog

Semua perubahan penting pada RekomFilm dicatat di sini (terbaru di atas).
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/).

## [2.2.2] - 2026-10-26

### Ditambahkan
- `AGENTS.md` (catatan proyek untuk agent AI / IDE / terminal: stack, peta kode, endpoint, aturan bisnis, konvensi, jebakan, tips hemat token) dan `CLAUDE.md` (mengimpor `AGENTS.md`).

## [2.2.1] - 2026-10-26

Audit bug menyeluruh untuk akun default (`admin`, `dewasa`, `remaja`, `anak`) dan akun yang baru mendaftar:
semua halaman dimuat tanpa error, lalu alur utama dicoba lewat browser (katalog, cari, filter, keranjang, checkout, tonton,
beli langsung, struk, preferensi, admin film/genre/pengguna/riwayat pembelian). Data uji dibersihkan setelahnya.

### Diperbaiki
- **Daftar**: email kembar kini ditolak (sebelumnya lolos); username dicek tanpa membedakan huruf besar/kecil
  (mencegah "Admin" bentrok dengan "admin" dan error 500 di PostgreSQL).
- **Genre**: menambah/mengganti nama genre dengan nama yang sudah ada sebelumnya **error 500**; kini pesan validasi yang jelas.
- **Login** tidak membedakan huruf besar/kecil pada username/email (penting di PostgreSQL). **Logout** hanya mengeluarkan perangkat
  yang dipakai, bukan semua perangkat.
- **Film nonaktif** tidak bisa lagi dilihat/dibeli/ditonton lewat ID (404).
- **Tonton** film berbayar sekarang mewajibkan film sudah dibeli (sebelumnya API tidak memeriksa).
- **Beli langsung** memvalidasi metode pembayaran dan menghapus film itu dari keranjang.
- **Keranjang**: film yang dinonaktifkan admin atau sudah dimiliki otomatis dibuang; checkout tanpa film yang bisa dibayar ditolak
  dengan pesan jelas (sebelumnya "berhasil 0 film").
- **Admin - Film**: semua genre sebuah film kini bisa dikosongkan (sebelumnya genre lama tetap tersimpan).
- **Waktu struk** tampil 7 jam lebih awal berlabel "WIB" (waktu UTC diberi label WIB). Kini dikonversi ke zona tampilan
  (`APP_DISPLAY_TIMEZONE`, default `Asia/Jakarta`); data yang sudah tersimpan tidak berubah.
- **Beranda**: respons lambat dari klik tab/pencarian sebelumnya tidak lagi menimpa daftar yang lebih baru.
- **Preferensi**: pengambilan nilai form dibuat pasti (`form.elements`).
- **Realtime**: bila Reverb tidak berjalan, browser berhenti mencoba ulang WebSocket (konsol tidak dipenuhi error); muncul satu pesan info.
- Pesan validasi kini **berbahasa Indonesia** (`lang/id/validation.php`, `APP_LOCALE=id`).
- `per_page` pada semua daftar dibatasi maksimal 100.

### Ditambahkan
- `tests/Feature/AccountSweepTest.php` (sapuan semua endpoint untuk akun default & akun baru, aturan usia, akun nonaktif)
  dan `tests/Feature/BugfixTest.php` (satu tes per bug di atas). Total 30 tes.
- `config('app.display_timezone')` / env `APP_DISPLAY_TIMEZONE`.

### Catatan
- Saat Reverb mati browser tetap mencatat 2 baris "WebSocket connection failed" per muat halaman (dicatat oleh browser sendiri
  sebelum aplikasi sempat berhenti mencoba). Jalankan `php artisan reverb:start` untuk menghilangkannya.

## [2.2.0] - 2026-10-26

### Ditambahkan
- **Mode gelap / terang** dengan tombol ikon (matahari/bulan).
  - Desktop (lebar >= 1024px): tombol di sisi **kiri** header, tepat setelah logo (ada jarak).
  - Mobile & tablet: tombol di **kiri nama akun** (ada jarak ke ikon keranjang & keluar).
  - Halaman login/daftar punya tombol sendiri di pojok kiri atas.
  - Pilihan tersimpan di `localStorage` dan dipasang sebelum render (tanpa kedip). Default: gelap.
- **Animasi perpindahan tema yang halus** (0,5 detik: latar, teks, border, bayangan) + ikon berputar.
  Dilewati otomatis bila perangkat memakai "kurangi gerakan" (`prefers-reduced-motion`).
- **Keranjang belanja** (ikon keranjang di header + lencana jumlah, halaman `/cart`):
  - Tombol "Tambah ke keranjang" di detail film (tombol "Beli sekarang" langsung tetap ada).
  - Ringkasan: **jumlah film**, subtotal, total bayar, dan **pilihan metode pembayaran** (QRIS, BCA VA, Mandiri VA).
  - Checkout membuat satu **nomor faktur** (`INV-...`) dan satu transaksi per film; faktur bisa dicetak.
  - Keranjang tersimpan di server (tabel `cart_items`) sehingga ikut ke semua perangkat.
  - Aturan: film gratis & film yang sudah dimiliki tidak bisa dimasukkan; batas usia (LSF) tetap diperiksa saat menambah dan saat checkout.
  - API: `GET /api/cart`, `POST /api/cart/{movie}`, `DELETE /api/cart/{movie}`, `POST /api/cart/checkout`.
- **Admin - Riwayat Pembelian** (`/admin/transactions`): seluruh transaksi semua pengguna, dengan pencarian
  (pengguna, film, no. faktur/order), filter metode pembayaran, paginasi, dan total pendapatan sesuai filter.
  API: `GET /api/admin/transactions`.
- **Admin - Ringkasan**: kartu **Total Transaksi** dan **Total Pendapatan**.
- Kolom `users.is_active`, `transactions.invoice_number`, dan tabel `cart_items` (3 migrasi baru, jalankan `php artisan migrate`).
- Kelas bantu `App\Support\AgeAccess` (aturan usia dipakai bersama oleh film dan keranjang).
- Tes otomatis baru: `tests/Feature/CartAndAdminTest.php` (keranjang, checkout, riwayat admin, aksi pengguna, poster besar).

### Diubah
- **Admin - Pengguna**: kolom "Peran" berbentuk dropdown diganti kolom **Aksi** berisi tombol **ikon** (bukan emoji),
  berurutan: **Hapus**, **Edit**, **Nonaktifkan/Aktifkan**, **Ubah Izin**. Peran dan status kini tampil sebagai lencana.
  - Hapus: pengguna beserta riwayat tonton, transaksi, dan keranjangnya ikut terhapus.
  - Edit: nama, tanggal lahir, rating minimum.
  - Nonaktifkan: pengguna langsung keluar (token dicabut) dan login ditolak sampai diaktifkan lagi.
  - Ubah Izin: pilih peran User atau Admin.
  - Admin tidak bisa menghapus/menonaktifkan/menurunkan **akunnya sendiri** (tombol terkunci + validasi di API).
  - Di layar kecil kolom yang kurang penting disembunyikan agar tombol aksi tetap terlihat.
- **Upload poster film (admin) tanpa batas ukuran dari aplikasi** (batas 2 MB dihapus; format jpeg/png/jpg/webp/gif/avif/svg).
  Batas nyata tinggal konfigurasi server: `upload_max_filesize` & `post_max_size` di `php.ini`
  (Laragon saat ini 2G; di Nginx/hosting lain cek juga `client_max_body_size`).
- Format harga: `RP. 10.000` menjadi `Rp 10.000`.
- Navigasi desktop kini mulai di lebar >= 1024px (tablet memakai bar navigasi bawah seperti mobile).
- Konfirmasi hapus memakai dialog bergaya shadcn (bukan `confirm()` bawaan browser).
- Warna teks/lencana disesuaikan agar kontras baik di mode terang maupun gelap.

### Diperbaiki
- Kotak scrollbar kecil di samping tab menu admin.
- Login gagal ("CSRF token mismatch") saat memakai domain sesuai `APP_URL` (`rekomendasi-film.test`):
  `statefulApi()` dihapus karena aplikasi memakai token Bearer.

## [2.1.0] - 2026-10-26

### Ditambahkan
- Dukungan **PHP 8.2 - 8.5** (lock disusun untuk baseline 8.2).
- Database fleksibel: **SQLite, MySQL, MariaDB, PostgreSQL** (atur `DB_CONNECTION`); pencarian memakai `whereLike` (case-insensitive di semua driver).
- **Redis** untuk cache/session/queue via `.env` (`REDIS_CLIENT=predis`, tanpa ekstensi PHP).
- **Realtime dengan Laravel Reverb**: perubahan katalog oleh admin langsung muncul sebagai toast dan memuat ulang daftar film
  (`app/Events/MovieChanged.php`, `resources/js/lib/realtime.js`). Jalankan `php artisan reverb:start`.
- Docker: pilihan versi PHP (`PHP_VERSION`) dan profile database/Redis di `docker-compose.yml`.
- Domain lokal `rekomendasi-film.test` (virtual host Laragon).

## [2.0.0] - 2026-10-26

### Diubah (rebase frontend)
- Frontend ditulis ulang: **Blade (1 view) + JavaScript vanilla (ES modules) + Tailwind CSS v4** bergaya shadcn/ui,
  tanpa framework JS dan tanpa jQuery. Satu file JS per halaman di `resources/js/pages/`.
- **PWA**: manifest dan service worker diperbarui (aset cache-first, halaman network-first, `/api` tidak pernah di-cache, halaman offline).
- Autentikasi API murni token Sanctum (tanpa session di endpoint API).
- Frontend lama dipindahkan ke `_legacy_frontend/` (cadangan, aman dihapus).
