# AGENTS.md — RekomFilm (catatan untuk agent / IDE / terminal)

Baca file ini dulu; jangan menelusuri seluruh repo. Bahasa UI, komentar, dan pesan: **Indonesia**.

## Ringkas
Web rekomendasi film berdasarkan usia (klasifikasi LSF: SU/13+/17+/21+), genre favorit, riwayat tonton, rating.
Ada keranjang belanja + checkout, struk, panel admin, PWA, dan realtime. Laravel 11 (API) + frontend vanilla JS.

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 11, PHP **8.2–8.5** (baseline 8.2: `composer.json` -> `config.platform.php=8.2.0`), Sanctum (token Bearer) |
| DB | SQLite (default dev) / MySQL / MariaDB / PostgreSQL — ganti `DB_CONNECTION`, **jangan** pakai SQL khusus satu driver |
| Cache/Session/Queue | database (default) atau Redis (`REDIS_CLIENT=predis`) |
| Realtime | Laravel Reverb (`php artisan reverb:start`, port 8080) |
| Frontend | Blade 1 view + **JavaScript vanilla (ES module)** + **Tailwind CSS v4**, gaya shadcn/ui, build Vite |
| PWA | `public/manifest.webmanifest` + `public/sw.js` (butuh HTTPS atau localhost) |

## Aturan keras (dari pemilik proyek)
- **Tanpa framework JS** (bukan React/Vue/jQuery). CSS hanya **Tailwind**. Ikon = SVG (`icon()` di `lib/ui.js`), **bukan emoji**.
- Perubahan kecil & aman: tiap perbaikan diberi tes; jangan memunculkan bug baru. Catat di `CHANGELOG.md`.
- Poster film **tanpa batas ukuran** dari aplikasi (batas hanya `php.ini`).

## Menjalankan
```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed        # akun & film contoh
npm install && npm run build            # atau: npm run dev
php artisan reverb:start                # opsional (realtime)
php artisan test                        # 30 tes, DB in-memory (phpunit.xml)
```
Lokal (Laragon): `http://rekomendasi-film.test` (virtual host otomatis ke `public/`; tidak perlu `artisan serve`).
Ubah `VITE_*` di `.env` => jalankan ulang `npm run build`. `public/build` tidak di-commit.

Akun seeder (kata sandi `password`): `admin` (admin, 30 th), `dewasa` (22, min rating 7), `remaja` (15, 6), `anak` (12, 5).

## Peta kode
```
routes/web.php            URL -> view `app` + nama halaman (data-page)
routes/api.php            semua endpoint /api (auth:sanctum; admin = middleware role:admin)
resources/views/app.blade.php   satu-satunya view; skrip tema inline di <head>
resources/css/app.css     Tailwind + token warna (:root / .dark), animasi tema, @media print (struk)
resources/js/main.js      guard login/admin, kerangka header+nav bawah, badge keranjang, muat pages/<nama>.js, daftar SW
resources/js/lib/api.js   fetch /api + token localStorage('auth_token'); 401 -> logout otomatis
resources/js/lib/ui.js    kelas shadcn (btn/input/card/badge...), icon(), toast, openDialog, confirmDialog,
                          movieCard/movieGrid, pager/bindPager, tableWrap/td, iconBtn, themeToggle, esc, on(), $/$$
resources/js/lib/theme.js gelap/terang + transisi halus (localStorage 'theme', default gelap)
resources/js/lib/realtime.js  Echo+Reverb dimuat dinamis; berhenti mencoba bila server mati
resources/js/pages/*.js   1 file/halaman: `export default async (root, user) => {}`
                          home, movie-dialog, cart, history, transactions, preferences, login/register(+auth.js),
                          admin-{dashboard,movies,genres,users,transactions}(+admin-nav.js)
app/Http/Controllers/Api/ Auth, Movie, Genre, Recommendation, Transaction, Cart ; Admin/{Movie,Genre,User,Stats,Transaction}
app/Http/Requests/        Register, UpdateProfile, Movie, Genre (validasi + prepareForValidation)
app/Models/               User, Movie, Genre, WatchHistory, Transaction, CartItem
app/Services/RecommendationService.php   skor = 0.5*afinitas + 0.3*Jaccard + 0.2*rating; cold start = urut rating
app/Support/AgeAccess.php aturan usia bersama (Movie/Cart): <13 Di Bawah Umur, 13-17 Remaja, >=18 Dewasa
app/Events/MovieChanged.php  siaran ke channel publik `movies` (ShouldBroadcastNow)
lang/id/validation.php    pesan validasi Indonesia
database/migrations, seeders (Genre 12, Movie ±20 film, User 4 akun)
tests/Feature             MovieRecommendation, CartAndAdmin, AccountSweep, Bugfix (+Example)
_legacy_frontend/         frontend Blade+jQuery lama — ABAIKAN (aman dihapus)
```

## Endpoint (prefix /api)
Publik: `POST register|login`. Login: username/email tidak peka huruf besar; akun `is_active=false` ditolak (422).
User (Bearer): `POST logout` (token aktif saja) · `GET|PUT me` · `GET genres` · `GET|PUT me/favorite-genres` ·
`GET movies?genre=&page=&per_page<=100` · `GET movies/search?q=` · `GET movies/{id}` · `POST movies/{id}/purchase{payment_method}` ·
`POST movies/{id}/watch` (film berbayar wajib sudah dibeli) · `GET me/watch-history` · `GET me/transactions` ·
`GET transactions/{id|order_number}` · `GET recommendations` · `GET cart` · `POST cart/{movie}` · `DELETE cart/{movie}` ·
`POST cart/checkout{payment_method}` (1 faktur `INV-…`, 1 transaksi/film).
Admin: `GET stats` · `apiResource movies` (multipart; ubah via POST + `_method=PUT`; kirim `genres_sent=1`) · `apiResource genres` ·
`GET users` · `PUT users/{id}` (name, birth_date, minimum_rating, role, is_active) · `DELETE users/{id}` · `GET transactions?q=&method=`.
Metode bayar sah: `Transaction::METHODS` (QRIS, BCA Virtual Account, Mandiri Virtual Account).

## Aturan bisnis penting
- Film hanya tampil di katalog/rekomendasi jika `is_active`, lolos usia (`Movie::forAge`) dan rating minimum pengguna (`minimum_rating`); admin diperlakukan seperti pengguna biasa di sisi katalog.
- Film gratis (`price<=0`) = otomatis dimiliki. Tonton mencatat `watch_histories` (boleh berulang).
- Keranjang: tak menerima film gratis/sudah dimiliki/di luar usia; item usang dibuang otomatis; checkout membuang item yang sudah dimiliki.
- Admin tak bisa menghapus/menonaktifkan/menurunkan **dirinya sendiri**. Menonaktifkan user = hapus semua token.
- Hapus user/film/genre memakai cascade FK (riwayat, transaksi, keranjang ikut terhapus).
- Waktu disimpan di `APP_TIMEZONE`; tampil via `config('app.display_timezone')` (default Asia/Jakarta, label WIB).

## Konvensi
- **Menambah halaman**: baris di `routes/web.php` + `resources/js/pages/<nama>.js` (nama = `data-page`; awalan `admin` otomatis admin-only di `main.js`).
- **Menambah endpoint**: route di `routes/api.php` -> controller -> (FormRequest) -> tes di `tests/Feature`. Daftar paginate pakai `$this->perPage($request, n)` (maks 100).
- UI = string template + `esc()` untuk data server (anti-XSS); event pakai `on(root,'click','[data-x]',fn)`.
- Tombol/badge/input ambil dari helper di `ui.js` (jangan salin string kelas). Mode terang+gelap: warna teks pakai pasangan `text-x-600 dark:text-x-400`.
- Query pencarian pakai `whereLike` (kompatibel semua DB). Hindari `enum`, `DB::raw` khusus driver.
- Validasi selalu di server; pesan lewat `lang/id/validation.php` (+ `attributes`).

## Jebakan yang sudah pernah terjadi
- **Tailwind**: `hidden` kalah oleh `inline-flex` pada tombol -> pakai `max-lg:hidden`/`lg:hidden`, bukan `hidden lg:inline-flex`.
- **Sanctum** menyimpan pengguna request pertama di satu tes -> panggil `app('auth')->forgetGuards()` saat ganti akun di tes.
- Jangan pakai `statefulApi()` (domain = `APP_URL` jadi wajib CSRF; app ini token Bearer).
- Form multipart + PUT harus `_method=PUT`; tanpa genre tercentang harus kirim `genres_sent`.
- `form.elements.name` (bukan `form.name`) untuk input bernama "name".
- Terminal Windows/Git-Bash: heredoc berisi banyak tanda kutip bisa gagal parse; tulis file dengan alat tulis file, skrip Python via file.
- `composer install` memerlukan PHP >= 8.2; jangan menaikkan baseline lock (cek `composer config platform.php`).
- Service worker hanya aktif di HTTPS/localhost; naikkan `VERSION` di `public/sw.js` bila aset cache berubah.
- Reverb mati => browser mencatat 2 error WebSocket per halaman (normal).

## Hemat token / konteks
- Jangan baca: `vendor/`, `node_modules/`, `public/build/`, `composer.lock`, `package-lock.json`, `_legacy_frontend/`, `storage/`.
- Cari dengan Grep/Glob, baca bagian file yang perlu saja. `DOKUMENTASI_PROYEK.md` & `rancangan_web_rekomendasi_film.md` = dokumen perancangan (diagram), bukan kode.
- Riwayat perubahan: `CHANGELOG.md`. Cara pakai/instal untuk manusia: `README.md`.
