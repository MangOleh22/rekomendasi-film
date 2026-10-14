# RekomFilm - Sistem Rekomendasi Film

Sistem rekomendasi film berbasis **Laravel 11** yang menyaring film berdasarkan batas usia resmi Lembaga Sensor Film (LSF: SU, 13+, 17+, 21+), preferensi genre, dan riwayat tontonan pengguna menggunakan algoritma bobot afinitas, kemiripan Jaccard, dan rating.

> **Dokumentasi Lengkap Perancangan Sistem:**  
> Seluruh diagram pemodelan arsitektur (Versi, ERD, LRS, Flowchart, Use Case, Activity Diagram) telah dipisahkan ke dalam berkas:  
> 📄 **[DOKUMENTASI_PROYEK.md](file:///c:/laragon/www/rekomendasi-film/DOKUMENTASI_PROYEK.md)**

---

## 1. Spesifikasi Teknis

- **Framework**: Laravel 11.57.0 (PHP 8.3.22)
- **Basis Data**: MySQL 8.0.30 / MariaDB
- **Autentikasi**: Laravel Sanctum & Web Session (Username)
- **Antarmuka**: Blade Templates, Vanilla CSS (gaya shadcn/ui netral gelap)
- **Frontend Script**: Vanilla JS + jQuery 3.7.1
- **Ikon**: Vektor SVG (tanpa emotikon)
- **PWA**: Web App Manifest & Service Worker (Offline Support)

---

## 2. Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan:
- PHP >= 8.2 (PHP 8.3 terpasang)
- Composer >= 2.x
- MySQL Server (Port 3306)

### Langkah Menjalankan:
```bash
# 1. Masuk ke direktori aplikasi
cd c:\laragon\www\rekomendasi-film

# 2. Pasang dependensi
composer install --no-security-blocking

# 3. Jalankan migrasi dan isi database (data film & akun awal)
php artisan migrate:fresh --seed

# 4. Hubungkan symlink storage poster
php artisan storage:link

# 5. Jalankan server lokal
php artisan serve --port=8000
```

Buka di peramban: `http://127.0.0.1:8000/login`

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
Semua filter usia, isolasi hak akses admin, dan logika rekomendasi telah terverifikasi lulus.
