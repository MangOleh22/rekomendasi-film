# RekomFilm - Sistem Rekomendasi Film

Sistem rekomendasi film berbasis **Laravel 11** yang menyaring film berdasarkan batas usia resmi Lembaga Sensor Film (LSF: SU, 13+, 17+, 21+), preferensi genre, dan riwayat tontonan pengguna menggunakan algoritma bobot afinitas, kemiripan Jaccard, dan rating.

> **Riwayat perubahan:** lihat [CHANGELOG.md](./CHANGELOG.md) · **Catatan untuk agent/IDE:** [AGENTS.md](./AGENTS.md)

> **Dokumentasi Lengkap Perancangan Sistem:**  
> Seluruh diagram pemodelan arsitektur (Versi, ERD, LRS, Flowchart, Use Case, Activity Diagram) telah dipisahkan ke dalam berkas:  
> 📄 **[DOKUMENTASI_PROYEK.md](./DOKUMENTASI_PROYEK.md)**

---

## 1. Spesifikasi Teknis

- **Framework**: Laravel 11.57.0 (Mendukung PHP 8.2 s.d. 8.4+)
- **Basis Data**: MySQL 8.0.30 / MariaDB
- **Autentikasi**: Laravel Sanctum & Web Session (Username)
- **Antarmuka**: Blade (1 view) + JavaScript vanilla (ES modules, tanpa framework/jQuery) + Tailwind CSS v4, gaya shadcn/ui
- **Build**: Vite (`npm run dev` / `npm run build`)
- **PWA**: `public/manifest.webmanifest` + `public/sw.js` (installable, offline fallback)

### Struktur Frontend
```
routes/web.php            daftar URL -> view `app` + nama halaman
resources/views/app.blade.php   satu-satunya view (memuat <body data-page="...">)
resources/css/app.css     Tailwind + variabel tema (warna) shadcn
resources/js/main.js      penjaga akses (login/admin), kerangka navigasi, memuat halaman, daftar service worker
resources/js/lib/api.js   fetch ke /api + token
resources/js/lib/ui.js    class gaya shadcn, ikon, toast, dialog, kartu film, tabel, paginasi
resources/js/pages/*.js   satu file per halaman: home, cart, history, transactions, preferences,
                          login, register, admin-dashboard/movies/genres/users/transactions
```
Menambah halaman: tambah baris di `routes/web.php` + file `resources/js/pages/<nama>.js` (`export default async (root, user) => {...}`).
Folder `_legacy_frontend/` berisi frontend Blade+jQuery lama (cadangan, aman dihapus).

---

## 1b. Konfigurasi Fleksibel (`.env`)

| Hal | Pilihan | Cara |
|---|---|---|
| **PHP** | 8.2 – 8.5 | Lock disusun untuk baseline 8.2 (`config.platform.php`). PHP 8.0/8.1 tidak didukung (Laravel 11 & Reverb mensyaratkan 8.2+). |
| **Database** | SQLite, MySQL, MariaDB, PostgreSQL | `DB_CONNECTION=sqlite\|mysql\|mariadb\|pgsql` + `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD`. Pastikan ekstensi `pdo_mysql` / `pdo_pgsql` aktif. |
| **Cache / Session / Queue** | database (default) atau Redis | `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` = `redis`. `REDIS_CLIENT=predis` tidak butuh ekstensi PHP. |
| **Realtime** | Laravel Reverb | `php artisan reverb:start` (port 8080). Kosongkan `VITE_REVERB_APP_KEY` untuk mematikan. Banyak server: `REVERB_SCALING_ENABLED=true` + Redis. |

Realtime saat ini: admin menambah/mengubah/menghapus film -> semua pengguna di Beranda mendapat toast dan daftar dimuat ulang (`app/Events/MovieChanged.php`, `resources/js/lib/realtime.js`).
Setelah mengubah `VITE_*` jalankan ulang `npm run build` / `npm run dev`.

## 2. Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan:
- PHP >= 8.2 (Mendukung penuh PHP 8.2, 8.3, dan 8.4+)
- Composer >= 2.x
- MySQL Server (Port 3306)

### Opsi 1: Menjalankan Lokal (Laragon / CLI)
```bash
# Masuk ke direktori aplikasi
cd c:\laragon\www\rekomendasi-film

# Pasang dependensi & setup database
composer install
npm install && npm run build   # build frontend (atau `npm run dev` saat pengembangan)
php artisan migrate:fresh --seed
php artisan storage:link

# Jalankan server lokal
php artisan serve --port=8000
php artisan reverb:start        # terminal lain (opsional, untuk realtime)
```
Buka di peramban: `http://127.0.0.1:8000/login`

### Opsi 2: Menjalankan via Docker
Aplikasi sudah dilengkapi dengan [Dockerfile](./Dockerfile) (versi PHP bisa dipilih lewat `PHP_VERSION`, 8.2–8.5) dan [docker-compose.yml](./docker-compose.yml). Database & Redis dipilih lewat profile (`--profile mysql|mariadb|postgres|redis`); set `DB_CONNECTION`/`DB_HOST` di `.env` sesuai pilihan:
```bash
# Bangun dan jalankan container
docker compose up -d --build

# Jalankan migrasi & seeder di dalam container
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link
```
Akses di: `http://localhost:8000/login`

### Opsi 3: Hosting Menggunakan Apache (cPanel / VPS / Laragon)
- **DocumentRoot**: Arahkan ke folder `/public` (misal: `/var/www/html/rekomendasi-film/public`).
- **Modul `mod_rewrite`**: Wajib aktif (sudah dikonfigurasi otomatis di [public/.htaccess](./public/.htaccess)).
- **Izin Berkas**: Pastikan folder `storage/` dan `bootstrap/cache/` memiliki izin tulis (writeable, chmod 775).

---

## 3. Akun Bawaan (Hasil Seeder)

Semua akun menggunakan kata sandi: `password`

| Username | Usia | Peran | Izin Akses Film |
|---|---|---|---|
| `admin` | 30 Tahun | Administrator | Panel Admin (`/admin`), CRUD film, genre, dan pengguna |
| `dewasa` | 22 Tahun | Pengguna | Semua klasifikasi (SU, 13+, 17+, 21+) |
| `remaja` | 15 Tahun | Pengguna | Klasifikasi SU dan 13+ |
| `anak` | 12 Tahun | Pengguna | Klasifikasi SU (Semua Usia) saja |

---

## 4. Pengujian Otomatis

Jalankan pengujian unit dan fitur menggunakan database in-memory:
```bash
php artisan test
```
Semua filter usia, modul transaksi & pembayaran, isolasi hak akses admin, dan logika rekomendasi telah terverifikasi lulus (9 tests, 47 assertions).

---

## 5. Catatan Pembaruan Rilis v1.1.0 (Changelog)

- **Penghapusan Badge Umur di Navbar Kanan Atas**: Area profil kanan atas kini bersih hanya menampilkan nama akun.
- **Modul Pembayaran & Pembelian Film**:
  - Kolom `price` pada film dengan format nominal Rupiah bertitik: `RP. 10.000`.
  - Input harga di panel admin dilengkapi pemisah titik otomatis.
  - Tombol **Beli Film** diletakkan di sebelah **kiri** tombol "Tandai Sudah Ditonton" dengan jarak (`gap: 1rem`).
- **Algoritma If-Else Kategori Umur**:
  - Batasan umur diimplementasikan dengan logika *if-else* terpadu: `Di Bawah Umur (< 13)`, `Remaja (13-17)`, dan `Dewasa (>= 18)`.
- **Struk Bukti Transaksi Resmi**:
  - Penerbitan struk pembayaran lunas setelah pembelian.
  - Tombol **Cetak Struk (PDF)** dan **Download Struk (Teks)**.
  - Halaman **Riwayat Transaksi** (`/transactions`) untuk melihat dan mencetak ulang seluruh riwayat struk.
- **Kesiapan REST API Mobile (Android & iOS)**: 29 endpoint API JSON murni dengan autentikasi Sanctum Bearer Token.

Untuk arsitektur teknis lengkap (ERD, LRS, Flowchart, dan Use Case terbaru), lihat:  
📄 **[DOKUMENTASI_PROYEK.md](./DOKUMENTASI_PROYEK.md)**
