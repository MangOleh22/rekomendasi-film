# Rancangan Web Rekomendasi Film Berdasarkan Genre, Usia, dan Preferensi Pengguna

Dokumen ini adalah rancangan untuk membangun web rekomendasi film dengan Laravel 11. Isinya mencakup komparasi dengan panduan Flask yang sudah ada, rancangan basis data, rumus rekomendasi, API, antarmuka, PWA, dan urutan pengerjaan.

## 1. Tujuan dan Ruang Lingkup

Web ini memberikan rekomendasi film berdasarkan:

- Genre favorit yang dipilih pengguna.
- Genre yang sering ditonton (dari riwayat tonton).
- Rating minimum yang diinginkan pengguna.
- Usia pengguna, dibandingkan dengan batas usia film.

Fitur utama:

- Halaman pengguna dan halaman admin.
- Login dan logout.
- Pencarian film.
- PWA (Progressive Web Application).
- API untuk film, akun, genre, pencarian, usia film, dan genre favorit.

Ketentuan teknis:

- Laravel 11.
- MySQL, dan juga dengan kompatibilatas mariadb versi terbaru.
- JavaScript tanpa framework.
- CSS tanpa framework.
- jQuery untuk memudahkan daftar film yang banyak di dashboard pengguna dan admin.
- Desain mengikuti gaya shadcn/ui: bersih, rapi, minimalis.
- Memakai ikon SVG, tidak memakai emotikon.

## 2. Komparasi dengan Panduan Flask

| Aspek | Panduan Flask | Kebutuhan Laravel | Penyesuaian |
|---|---|---|---|
| Framework | Flask, satu file `app.py` | Laravel 11 | Struktur MVC, bukan salinan kode |
| Database | SQLite | MySQL/MariaDB | Migration dan seeder Laravel |
| Akun | Tidak ada, nama dan usia diisi tiap kali | Login, logout, profil | Profil tersimpan di database |
| Usia pengguna | Input angka 1 sampai 100 | Menyaring film | Simpan tanggal lahir, hitung usia otomatis |
| Batas usia film | Dictionary `AGE_RATINGS` di kode, per judul | Sesuai usia pengguna | Kolom `age_limit` di tabel film, bisa diatur admin |
| Genre | Daftar `GENRES` di kode | API genre | Tabel `genres` dan tabel pivot |
| Genre sering ditonton | Tidak ada | Genre favorit | Tabel riwayat tonton |
| Skor | `similarity * 80 + rating * 2` | Kemiripan genre dan rating | Logika dipertahankan, rumus dinormalisasi |
| Admin | Tidak ada | Ada | CRUD film, genre, pengguna |
| Pencarian | Tidak ada | Ada | Endpoint pencarian dengan filter usia |
| API | Tidak ada | Ada | REST API dengan Sanctum |
| PWA | Tidak ada | Ada | Manifest dan service worker |
| UI | Gaya sendiri, ada emoji | Gaya shadcn, tanpa emoji | Ikon SVG |

Yang dapat dipakai ulang dari panduan Flask:

- Logika penyaringan usia dan rating minimum.
- Jaccard similarity untuk kemiripan genre.
- Validasi input.
- Penjelasan bahwa skor hanyalah nilai pemeringkatan, bukan probabilitas.

Catatan penting tentang shadcn/ui: pustaka aslinya berbasis React dan Tailwind, sedangkan kebutuhan proyek ini adalah JS dan CSS tanpa framework. Karena itu yang ditiru adalah bahasa desainnya (warna netral, border tipis, radius sedang, bayangan halus), diterapkan dengan CSS biasa dan variabel CSS.

## 3. Arsitektur

```text
Browser (HTML + JS + CSS + jQuery, PWA)
        |
        |  fetch / AJAX (JSON)
        v
Laravel 11
  - Routes: web.php, api.php
  - Middleware: auth:sanctum, role:admin
  - Controllers -> Form Request -> Service -> Eloquent Model
  - RecommendationService (logika skor)
        |
        v
MySQL / MariaDB
```

Prinsip yang dipakai:

- Controller tipis, logika bisnis ada di Service.
- Validasi memakai Form Request.
- Filter usia diterapkan di satu tempat (scope Eloquent) dan dipakai di semua query film.
- Hak akses admin diperiksa di server, bukan hanya disembunyikan di antarmuka.

## 4. Rancangan Basis Data

### 4.1 Tabel

| Tabel | Kolom utama |
|---|---|
| `users` | id, name, email, password, birth_date, role (`admin`/`user`), minimum_rating, timestamps |
| `genres` | id, name, slug |
| `movies` | id, title, slug, synopsis, year, rating (0 sampai 10), age_limit, poster_path, is_active, timestamps |
| `genre_movie` | movie_id, genre_id |
| `user_favorite_genres` | user_id, genre_id |
| `watch_histories` | id, user_id, movie_id, watched_at |

### 4.2 Ketentuan kolom dan indeks

- `age_limit` bertipe angka kecil dengan nilai 0, 13, 17, atau 21. Nilai 0 berarti semua usia (SU).
- `rating` bertipe desimal (misalnya `decimal(3,1)`).
- Indeks: `movies(age_limit, rating)`, `movies(title)`, `watch_histories(user_id, movie_id)`.
- Unique: `genres.slug`, `movies.slug`, `users.email`, pasangan `(movie_id, genre_id)`, pasangan `(user_id, genre_id)`.
- Foreign key dengan `cascadeOnDelete` pada tabel pivot dan riwayat.

### 4.3 Klasifikasi usia

Untuk data nyata, gunakan klasifikasi Lembaga Sensor Film (LSF) Indonesia: SU, 13+, 17+, 21+. Panduan Flask hanya memakai 0, 13, dan 17, jadi nilai 21 perlu ditambahkan. Angka batas usia pada panduan Flask adalah data demonstrasi, jadi seeder perlu diisi dengan klasifikasi resmi yang sesuai.

### 4.4 Usia pengguna

Simpan `birth_date`, lalu hitung usia di model:

```php
// app/Models/User.php
use Carbon\Carbon;

public function getAgeAttribute(): int
{
    return Carbon::parse($this->birth_date)->age;
}
```

Di antarmuka, tampilkan usia saja. Tanggal lahir adalah data pribadi, jadi endpoint profil harus dilindungi.

### 4.5 Scope filter usia

```php
// app/Models/Movie.php
public function scopeForAge($query, int $age)
{
    return $query->where('age_limit', '<=', $age);
}

public function scopeMinRating($query, float $rating)
{
    return $query->where('rating', '>=', $rating);
}
```

## 5. Logika Rekomendasi

### 5.1 Tahap penyaringan (wajib, di server)

1. `age_limit <= usia pengguna`.
2. `rating >= rating minimum`.
3. `is_active = true`.
4. Film yang sudah ditonton dikeluarkan (atau diberi penalti).

Penyaringan ini bukan bagian dari skor. Film di luar batas usia tidak boleh muncul sama sekali.

### 5.2 Bobot genre pengguna

Untuk setiap genre `g`:

- `h(g)` = jumlah tontonan genre g dibagi jumlah tontonan genre terbanyak (0 sampai 1, bernilai 0 jika belum ada riwayat).
- `f(g)` = 1 jika genre g dipilih sebagai favorit, selain itu 0.

```text
Jika ada riwayat tonton:  w(g) = 0.7 * h(g) + 0.3 * f(g)
Jika belum ada riwayat:   w(g) = f(g)
```

### 5.3 Skor film

```text
afinitas  = rata-rata w(g) untuk semua genre milik film
jaccard   = |genre film ∩ genre favorit| / |genre film ∪ genre favorit|
rating_n  = rating film / 10

Skor = 100 * (0.5 * afinitas + 0.3 * jaccard + 0.2 * rating_n)
```

Penjelasan:

- Semua komponen berskala 0 sampai 1, sehingga bobot mudah diatur dan dijelaskan.
- Bobot 0.5 / 0.3 / 0.2 adalah titik awal. Sesuaikan setelah dicoba dengan data nyata.
- Skor adalah nilai pemeringkatan internal, bukan probabilitas bahwa pengguna pasti menyukai film tersebut.
- Urutkan berdasarkan skor, lalu rating sebagai pemisah jika skor sama.

### 5.4 Pengguna baru (cold start)

Jika pengguna belum punya riwayat maupun genre favorit, tampilkan film dengan rating tinggi yang lolos filter usia.

### 5.5 Kerangka `RecommendationService`

```php
<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    public function forUser(User $user, int $limit = 20): Collection
    {
        $favoriteIds = $user->favoriteGenres()->pluck('genres.id')->all();

        // Jumlah tontonan per genre.
        $counts = DB::table('watch_histories as wh')
            ->join('genre_movie as gm', 'gm.movie_id', '=', 'wh.movie_id')
            ->where('wh.user_id', $user->id)
            ->groupBy('gm.genre_id')
            ->selectRaw('gm.genre_id, COUNT(*) as total')
            ->pluck('total', 'genre_id');

        $maxCount = $counts->max() ?: 0;
        $hasHistory = $maxCount > 0;

        $weights = collect($favoriteIds)->merge($counts->keys())->unique()
            ->mapWithKeys(function ($genreId) use ($counts, $maxCount, $favoriteIds, $hasHistory) {
                $f = in_array($genreId, $favoriteIds) ? 1 : 0;
                $h = $hasHistory ? ($counts[$genreId] ?? 0) / $maxCount : 0;
                $w = $hasHistory ? (0.7 * $h + 0.3 * $f) : $f;
                return [$genreId => $w];
            });

        $watchedIds = DB::table('watch_histories')
            ->where('user_id', $user->id)
            ->pluck('movie_id');

        $movies = Movie::query()
            ->with('genres:id,name')
            ->where('is_active', true)
            ->forAge($user->age)
            ->minRating((float) $user->minimum_rating)
            ->whereNotIn('id', $watchedIds)
            ->get();

        return $movies
            ->map(function (Movie $movie) use ($weights, $favoriteIds) {
                $genreIds = $movie->genres->pluck('id');

                $affinity = $genreIds->isEmpty()
                    ? 0
                    : $genreIds->avg(fn ($id) => $weights[$id] ?? 0);

                $union = $genreIds->merge($favoriteIds)->unique()->count();
                $inter = $genreIds->intersect($favoriteIds)->count();
                $jaccard = $union > 0 ? $inter / $union : 0;

                $score = 100 * (
                    0.5 * $affinity +
                    0.3 * $jaccard +
                    0.2 * ($movie->rating / 10)
                );

                $movie->score = round($score, 2);
                return $movie;
            })
            ->filter(fn ($movie) => $movie->score > 0)
            ->sortByDesc(fn ($movie) => [$movie->score, $movie->rating])
            ->take($limit)
            ->values();
    }
}
```

Untuk katalog yang besar, kerangka ini perlu dioptimalkan (misalnya menyaring genre di SQL lebih dulu atau menyimpan cache bobot genre per pengguna).

## 6. Rancangan API

Semua endpoint berformat JSON. Endpoint selain login memerlukan `auth:sanctum`.

### 6.1 Akun

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/login` | Login |
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Profil dan usia pengguna |
| PUT | `/api/me` | Ubah profil dan rating minimum |

### 6.2 Genre

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/genres` | Daftar genre |
| GET | `/api/me/favorite-genres` | Genre favorit pengguna |
| PUT | `/api/me/favorite-genres` | Simpan genre favorit |

### 6.3 Film

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/movies` | Daftar film (sudah disaring usia), dengan paginasi dan filter genre |
| GET | `/api/movies/search?q=` | Pencarian (tetap disaring usia) |
| GET | `/api/movies/{id}` | Detail film (ditolak jika di luar batas usia) |
| GET | `/api/recommendations` | Rekomendasi dengan skor |
| POST | `/api/movies/{id}/watch` | Catat tontonan |

### 6.4 Admin

| Method | Endpoint | Fungsi |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/admin/movies` | CRUD film |
| GET/POST/PUT/DELETE | `/api/admin/genres` | CRUD genre |
| GET/PUT | `/api/admin/users` | Kelola pengguna dan peran |

Aturan penting: filter usia harus berlaku di **semua** endpoint film, termasuk pencarian dan detail. Jika hanya diterapkan pada rekomendasi, pengguna masih bisa melihat film di luar batas usia lewat pencarian atau URL langsung.

Contoh respons `/api/recommendations`:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Inception",
      "year": 2010,
      "rating": 8.8,
      "age_limit": 13,
      "age_label": "13+",
      "genres": ["Sci-Fi", "Action"],
      "score": 74.5
    }
  ]
}
```

Label usia: tampilkan `0` sebagai "Semua usia" (SU).

## 7. Autentikasi dan Keamanan

- Laravel Sanctum untuk autentikasi (web dan API berada dalam satu aplikasi).
- Password disimpan dengan hashing bawaan Laravel.
- Middleware `role:admin` untuk semua route admin.
- Form Request untuk validasi: nama, email, tanggal lahir (tidak boleh di masa depan dan harus wajar), rating minimum (0 sampai 10), genre harus ada di tabel `genres`.
- Perlindungan CSRF untuk halaman web, dan pembatasan percobaan login (rate limiting).
- Output dilindungi dari XSS (gunakan `{{ }}` di Blade, dan `textContent` bukan `innerHTML` untuk data dari pengguna di JS).
- Upload poster divalidasi tipe dan ukuran file.

## 8. Antarmuka

### 8.1 Halaman pengguna

- Login dan registrasi (termasuk tanggal lahir).
- Dashboard: rekomendasi, daftar film, pencarian.
- Pengaturan preferensi: genre favorit dan rating minimum.
- Detail film dan tombol catat tontonan.
- Riwayat tonton.

### 8.2 Halaman admin

- Dashboard ringkasan (jumlah film, genre, pengguna).
- Tabel film dengan pencarian, filter, dan paginasi.
- Formulir tambah/ubah film (judul, sinopsis, tahun, rating, batas usia, genre, poster).
- Kelola genre dan pengguna.

### 8.3 Gaya desain (mengikuti shadcn tanpa framework)

Contoh variabel CSS dasar:

```css
:root {
  --background: #ffffff;
  --foreground: #0a0a0a;
  --muted: #f4f4f5;
  --muted-foreground: #71717a;
  --border: #e4e4e7;
  --primary: #18181b;
  --primary-foreground: #fafafa;
  --destructive: #dc2626;
  --ring: #a1a1aa;
  --radius: 0.5rem;
  --font-sans: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}

@media (prefers-color-scheme: dark) {
  :root {
    --background: #09090b;
    --foreground: #fafafa;
    --muted: #27272a;
    --muted-foreground: #a1a1aa;
    --border: #27272a;
    --primary: #fafafa;
    --primary-foreground: #18181b;
    --ring: #52525b;
  }
}
```

Komponen yang perlu dibuat dengan CSS biasa: tombol (primary, outline, ghost), input, select, kartu, badge (untuk batas usia dan genre), tabel, dialog, dan notifikasi.

Ketentuan ikon:

- Gunakan ikon SVG (misalnya set Lucide), disimpan sebagai sprite atau file SVG lokal.
- Tidak ada emotikon di antarmuka, termasuk pada sapaan dan label.

### 8.4 Penggunaan jQuery

jQuery dipakai untuk membantu daftar film yang banyak:

- Merender kartu atau baris tabel dari hasil API.
- Paginasi atau tombol "muat lebih banyak".
- Pencarian dengan jeda ketik (debounce).
- Filter genre di dashboard pengguna dan admin.

Logika inti (usia, skor, hak akses) tetap di server, bukan di JavaScript.

## 9. PWA

File yang dibutuhkan:

- `public/manifest.webmanifest`: nama, `short_name`, `start_url`, `display: standalone`, warna tema, ikon 192 dan 512 piksel.
- `public/sw.js`: service worker.
- Halaman `offline.html` sederhana.

Strategi cache:

- File statis (CSS, JS, ikon, font): cache dengan versi.
- Halaman dan poster: `stale-while-revalidate` atau jaringan lebih dulu.
- Respons API yang bersifat personal (profil, rekomendasi, riwayat): **jangan di-cache**, karena berisi data pribadi dan bergantung pada usia pengguna.
- Saat logout, bersihkan data yang tersimpan di sisi klien.

PWA perlu HTTPS di server produksi.

## 10. Struktur Proyek (ringkas)

```text
app/
  Http/
    Controllers/
      Api/ (AuthController, MovieController, GenreController, RecommendationController)
      Admin/ (MovieController, GenreController, UserController)
    Middleware/ (EnsureRole)
    Requests/
  Models/ (User, Movie, Genre, WatchHistory)
  Services/ (RecommendationService)
database/
  migrations/
  seeders/ (GenreSeeder, MovieSeeder, AdminSeeder)
public/
  css/ (app.css)
  js/ (app.js, api.js, dashboard.js, admin.js)
  icons/ (svg)
  manifest.webmanifest
  sw.js
resources/views/
  layouts/
  auth/
  user/
  admin/
routes/
  web.php
  api.php
tests/
  Feature/ (AgeFilterTest, RecommendationTest, AdminAccessTest)
```

## 11. Urutan Pengerjaan

| Tahap | Pekerjaan | Hasil |
|---|---|---|
| 1 | Instal Laravel 11, konfigurasi MySQL/MariaDB, migration, model, seeder | Struktur data dan data contoh |
| 2 | Autentikasi, profil, role admin | Login, logout, hak akses |
| 3 | CRUD admin untuk film dan genre | Admin dapat mengelola data |
| 4 | API film, pencarian, dan filter usia | Daftar film aman sesuai usia |
| 5 | Riwayat tonton dan `RecommendationService` | Endpoint rekomendasi |
| 6 | Antarmuka pengguna dan admin (CSS, ikon SVG, jQuery) | Tampilan lengkap |
| 7 | PWA dan halaman offline | Aplikasi dapat dipasang |
| 8 | Pengujian dan perbaikan | Filter usia dan skor terverifikasi |

## 12. Daftar Pengujian

- Pengguna berusia 12 tahun tidak melihat film 13+, 17+, atau 21+ di daftar, pencarian, detail, maupun rekomendasi.
- Pengguna berusia tepat 13 tahun dapat melihat film 13+.
- Film dengan rating di bawah rating minimum tidak muncul.
- Film yang sudah ditonton tidak muncul di rekomendasi.
- Pengguna tanpa riwayat dan tanpa genre favorit mendapat film rating tinggi.
- Pengguna biasa tidak dapat mengakses route admin (hasil 403).
- Respons API personal tidak tersimpan di cache service worker.
- Validasi tanggal lahir menolak tanggal di masa depan.
- Tampilan berfungsi di layar ponsel dan desktop.
- Tidak ada emotikon di antarmuka.

## 13. Pengembangan Lanjutan

- Rating dan ulasan dari pengguna.
- Daftar favorit dan "tonton nanti".
- Integrasi API film untuk data dan klasifikasi usia yang lebih mutakhir.
- Pengelolaan profil anak dengan PIN orang tua.
- Penyetelan bobot skor berdasarkan data klik dan tontonan nyata.

## 14. Keputusan yang Perlu Ditetapkan

1. Sumber data film: input manual oleh admin, impor CSV, atau API film.
2. Apakah registrasi terbuka untuk umum atau dibuat oleh admin saja.
3. Apakah pengguna boleh mengubah tanggal lahir sendiri (mengubahnya dapat membuka film dengan batas usia lebih tinggi).
4. Bobot skor akhir (0.5 / 0.3 / 0.2 adalah nilai awal).
5. Versi MySQL atau MariaDB yang dipakai di server produksi.
