# Dokumentasi Arsitektur & Perancangan Sistem RekomFilm

Dokumen ini memuat spesifikasi teknis lengkap, pemodelan data (ERD & LRS), diagram alur kerja (Flowchart), Use Case, Activity Diagram, serta **Catatan Pembaruan (Changelog)** untuk sistem rekomendasi film berbasis **Laravel 11**.

---

## 1. Versi & Spesifikasi Teknologi

| Komponen | Spesifikasi / Versi | Detail Teknis |
|---|---|---|
| **Versi Rilis Aplikasi** | v1.1.0 (Pembaruan Pembayaran & Limitasi Umur) | Semantic versioning |
| **Bahasa Pemrograman** | PHP >= 8.2 s.d. 8.4+ | Teruji di PHP 8.3 bawaan Laragon, kompatibel PHP 8.2 dan 8.4+ |
| **Framework Web** | Laravel 11.57.0 | Pola MVC, Service Layer, Form Request, Sanctum |
| **Basis Data** | MySQL 8.0.30 / MariaDB | Relasi cascadeOnDelete, indeks majemuk usia, rating & transaksi |
| **Autentikasi** | Session Web & Sanctum Token | Kredensial berbasis `username` unik dan password ter-hash |
| **Desain Antarmuka** | Vanilla CSS (Gaya shadcn/ui) | Tema gelap netral zinc, radius sedang, cetak struk via `@media print` |
| **JavaScript** | Vanilla JS + jQuery 3.7.1 | Manipulasi DOM, format nominal Rupiah otomatis, print & download struk |
| **Ikonografi** | Ikon Vektor SVG (Lucide) | Murni berkas/vektor SVG, tanpa emotikon |
| **Kesiapan Mobile** | RESTful JSON API (29 Endpoints) | Siap diintegrasikan dengan aplikasi mobile Android & iOS |

---

## 2. Entity Relationship Diagram (ERD)

Diagram pemodelan basis data konseptual dan relasi kardinalitas antar entitas:

```mermaid
erDiagram
    USERS ||--o{ USER_FAVORITE_GENRES : chooses
    USERS ||--o{ WATCH_HISTORIES : watches
    USERS ||--o{ TRANSACTIONS : pays
    MOVIES ||--o{ TRANSACTIONS : purchased_in
    MOVIES ||--o{ WATCH_HISTORIES : recorded_in
    MOVIES ||--o{ GENRE_MOVIE : has
    GENRES ||--o{ GENRE_MOVIE : categorized
    GENRES ||--o{ USER_FAVORITE_GENRES : favored

    USERS {
        bigint id PK
        string username
        string name
        string email
        string password
        date birth_date
        string role
        decimal minimum_rating
        datetime created_at
        datetime updated_at
    }

    MOVIES {
        bigint id PK
        string title
        string slug
        text synopsis
        int year
        decimal rating
        bigint price "Harga Film dalam Rupiah"
        tinyint age_limit "Batas Usia 0, 13, 17, 21"
        string poster_path
        boolean is_active
        datetime created_at
        datetime updated_at
    }

    GENRES {
        bigint id PK
        string name
        string slug
        datetime created_at
        datetime updated_at
    }

    GENRE_MOVIE {
        bigint id PK
        bigint movie_id FK
        bigint genre_id FK
    }

    USER_FAVORITE_GENRES {
        bigint id PK
        bigint user_id FK
        bigint genre_id FK
        datetime created_at
        datetime updated_at
    }

    WATCH_HISTORIES {
        bigint id PK
        bigint user_id FK
        bigint movie_id FK
        datetime watched_at
        datetime created_at
        datetime updated_at
    }

    TRANSACTIONS {
        bigint id PK
        string order_number "Nomor Order Unik TRX"
        bigint user_id FK
        bigint movie_id FK
        bigint amount "Nominal Pembayaran"
        string payment_method "QRIS, BCA VA, Mandiri VA"
        string status "success, pending, failed"
        datetime paid_at
        datetime created_at
        datetime updated_at
    }
```

---

## 3. Logical Record Structure (LRS)

Representasi struktur logis antar tabel relasional beserta atribut kunci primer (PK) dan kunci tamu (FK):

```mermaid
classDiagram
    class USERS {
        +bigint id PK
        +string username
        +string name
        +string password
        +date birth_date
        +string role
        +decimal minimum_rating
    }

    class GENRES {
        +bigint id PK
        +string name
        +string slug
    }

    class MOVIES {
        +bigint id PK
        +string title
        +string slug
        +int year
        +decimal rating
        +bigint price
        +tinyint age_limit
        +boolean is_active
    }

    class GENRE_MOVIE {
        +bigint id PK
        #bigint movie_id FK
        #bigint genre_id FK
    }

    class USER_FAVORITE_GENRES {
        +bigint id PK
        #bigint user_id FK
        #bigint genre_id FK
    }

    class WATCH_HISTORIES {
        +bigint id PK
        #bigint user_id FK
        #bigint movie_id FK
        +datetime watched_at
    }

    class TRANSACTIONS {
        +bigint id PK
        +string order_number
        #bigint user_id FK
        #bigint movie_id FK
        +bigint amount
        +string payment_method
        +string status
        +datetime paid_at
    }

    USERS "1" --> "0..*" USER_FAVORITE_GENRES : has
    GENRES "1" --> "0..*" USER_FAVORITE_GENRES : belongs_to
    MOVIES "1" --> "0..*" GENRE_MOVIE : classified_in
    GENRES "1" --> "0..*" GENRE_MOVIE : groups
    USERS "1" --> "0..*" WATCH_HISTORIES : logs
    MOVIES "1" --> "0..*" WATCH_HISTORIES : recorded_in
    USERS "1" --> "0..*" TRANSACTIONS : pays
    MOVIES "1" --> "0..*" TRANSACTIONS : purchased_in
```

---

## 4. Flowchart Logika Rekomendasi & Filter Usia LSF

Alur kerja evaluasi dan pembobotan film pada `RecommendationService`:

```mermaid
flowchart TD
    Start(["Permintaan Rekomendasi User"]) --> GetUser["Ambil Usia dan Rating Min Pengguna"]
    GetUser --> FilterAge["Penyaringan Algoritma If-Else Kategori Usia User & Film"]
    FilterAge --> FilterRating["Penyaringan: rating >= user.minimum_rating"]
    FilterRating --> FilterActive["Penyaringan: is_active = true"]
    FilterActive --> ExcludeWatched["Keluarkan Film dari Riwayat Tonton (watch_histories)"]
    
    ExcludeWatched --> CheckColdStart{"Pengguna Memiliki Riwayat atau Favorit?"}
    
    CheckColdStart -- "Tidak (Kasus Cold Start)" --> ColdStartSort["Urutkan Kandidat Film Berdasarkan Rating Tertinggi"]
    ColdStartSort --> TakeLimit["Ambil 20 Film Teratas"]
    
    CheckColdStart -- "Ya" --> CalcWeights["Hitung Bobot Genre Tiap g: w(g)"]
    CalcWeights --> CalcScore["Hitung Skor Rekomendasi Tiap Film"]
    CalcScore --> FilterPositive["Saring Film dengan Skor > 0"]
    FilterPositive --> SortRank["Urutkan Skor Tertinggi (Pemecah Seri: Rating)"]
    SortRank --> TakeLimit
    
    TakeLimit --> ReturnResponse(["Kembalikan Koleksi Rekomendasi"])
```

---

## 5. Use Case Diagram

Interaksi peran **Pengguna (User)** dan **Administrator (Admin)** terhadap fungsionalitas sistem:

```mermaid
flowchart LR
    subgraph Aktor
        U["Pengguna (User)"]
        A["Administrator"]
    end

    subgraph "Sistem RekomFilm"
        UC1["Login / Registrasi (Token & Session)"]
        UC2["Lihat Daftar Rekomendasi Berbobot"]
        UC3["Eksplorasi Katalog & Cari Film (Debounce)"]
        UC4["Beli Film & Checkout Pembayaran (Format Rupiah)"]
        UC5["Lihat, Cetak (PDF), dan Unduh Struk Transaksi"]
        UC6["Tandai Film Sudah Ditonton"]
        UC7["Lihat Riwayat Tontonan & Transaksi"]
        UC8["Atur Preferensi (Genre Favorit & Rating Min)"]
        UC9["Kelola CRUD Film & Unggah Poster & Atur Harga"]
        UC10["Kelola Kategori Genre"]
        UC11["Kelola Akun & Peran Pengguna"]
        UC12["Lihat Statistik Ringkasan Sistem"]
    end

    U --> UC1
    U --> UC2
    U --> UC3
    U --> UC4
    U --> UC5
    U --> UC6
    U --> UC7
    U --> UC8

    A --> UC1
    A --> UC9
    A --> UC10
    A --> UC11
    A --> UC12
```

---

## 6. Activity Diagram

### 6.1 Aktivitas Pengguna: Eksplorasi, Pembelian, dan Cetak Struk

```mermaid
flowchart TD
    Start(["Mulai"]) --> MasukDashboard["Buka Katalog Rekomendasi"]
    MasukDashboard --> KlikDetail["Buka Modal Detail Film"]
    KlikDetail --> CekBeli{"Sudah Dibeli / Film Gratis?"}
    
    CekBeli -- "Sudah Dibeli" --> TombolStruk["Tampilkan Tombol 'Lihat Struk'"]
    TombolStruk --> BukaStruk["Buka Modal Bukti Transaksi Resmi"]
    
    CekBeli -- "Belum Dibeli" --> KlikBeli["Klik Tombol 'Beli Film (RP. 10.000)' di Kiri Tombol Tonton"]
    KlikBeli --> BukaPaymentModal["Buka Dialog Pembayaran"]
    BukaPaymentModal --> PilihMetode["Pilih Metode Pembayaran (QRIS / VA)"]
    PilihMetode --> KonfirmasiBayar["Klik 'Bayar Sekarang'"]
    
    KonfirmasiBayar --> ValidasiUmur{"Lolos Validasi Kategori Usia If-Else?"}
    ValidasiUmur -- "Tidak" --> GagalBayar["Pesan Error: Ditolak Batas Usia"] --> Selesai(["Selesai"])
    ValidasiUmur -- "Ya" --> SuksesBayar["Buat Nomor TRX, Catat Database LUNAS"]
    
    SuksesBayar --> BukaStruk
    BukaStruk --> PilihanCetak{"Pilih Aksi Struk"}
    PilihanCetak --> CetakStruk["Cetak Struk (PDF / Printer via window.print)"]
    PilihanCetak --> DownloadStruk["Unduh Struk File Teks (.txt)"]
    
    CetakStruk --> Selesai
    DownloadStruk --> Selesai
```

---

## 7. Catatan Pembaruan & Riwayat Perubahan (Changelog Pembaruan v1.1.0)

Berikut adalah rekapitulasi detail seluruh penambahan fitur dan pembaruan arsitektur sistem pada rilis **v1.1.0**:

### 🎯 Pembaruan 1: Penghapusan Span Badge Umur di Header Navigasi Kanan Atas
- **File Terdampak**: [resources/views/layouts/app.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/layouts/app.blade.php)
- **Keterangan**:
  - Elemen badge umur (`<span class="badge badge-13">{{ auth()->user()->age }} Tahun</span>`) yang sebelumnya berada di sudut kanan atas sebelah kiri nama akun pengguna/admin telah **dihapus**.
  - Area navbar kanan atas kini lebih bersih dan konsisten, hanya menampilkan nama akun serta tombol *logout*.

---

### 💰 Pembaruan 2: Modul Pembayaran Film & Format Nominal Rupiah
- **File Terdampak**:
  - [database/migrations/2026_10_21_132901_add_price_to_movies_table.php](file:///c:/laragon/www/rekomendasi-film/database/migrations/2026_10_21_132901_add_price_to_movies_table.php)
  - [app/Models/Movie.php](file:///c:/laragon/www/rekomendasi-film/app/Models/Movie.php)
  - [app/Http/Requests/MovieRequest.php](file:///c:/laragon/www/rekomendasi-film/app/Http/Requests/MovieRequest.php)
  - [resources/views/admin/movies.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/admin/movies.blade.php)
  - [resources/views/user/dashboard.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/user/dashboard.blade.php)
- **Keterangan**:
  1. **Kolom Harga Basis Data**: Menambahkan kolom `price` (`unsignedBigInteger`, default: `0`) pada tabel `movies`.
  2. **Format Penulisan Rupiah dengan Titik**:
     - Standar format harga menggunakan awalan `RP. ` dan pemisah ribuan titik (`.`), contoh: `RP. 10.000` (bukan digabung `RP. 10000`).
     - Diimplementasikan melalui aksesor model `formatted_price` di `Movie.php`.
  3. **Manajemen di Back Office / Admin Panel**:
     - Menambahkan kolom **Harga Film** pada tabel daftar film admin.
     - Menambahkan input **Harga Film (Rupiah)** pada formulir tambah & edit film.
     - Dilengkapi *auto-formatting* berbasis JavaScript yang otomatis menyisipkan titik saat admin mengetik angka nominal (contoh: mengetik `25000` otomatis terformat menjadi `25.000`).
  4. **Tata Letak Tombol Pembelian di Detail Film**:
     - Tombol **Beli Film (`RP. 10.000`)** diletakkan di bagian detail film, tepat di sebelah **KIRI** tombol **"Tandai Sudah Ditonton"**.
     - Diberikan jarak renggang (*spacing*) yang jelas (`gap: 1rem`) dari tombol "Tandai Sudah Ditonton" agar nyaman diakses pengguna.

---

### 🛡️ Pembaruan 3: Klasifikasi Kategori Umur Menggunakan Algoritma If-Else
- **File Terdampak**:
  - [app/Models/User.php](file:///c:/laragon/www/rekomendasi-film/app/Models/User.php) (`getAgeCategoryAttribute`)
  - [app/Models/Movie.php](file:///c:/laragon/www/rekomendasi-film/app/Models/Movie.php) (`getAgeCategoryAttribute`, `scopeForAge`)
  - [app/Http/Controllers/Api/MovieController.php](file:///c:/laragon/www/rekomendasi-film/app/Http/Controllers/Api/MovieController.php) (`evaluateAgeAccess`)
  - [resources/views/user/dashboard.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/user/dashboard.blade.php)
  - [resources/views/user/history.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/user/history.blade.php)
  - [resources/views/admin/movies.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/admin/movies.blade.php)
- **Keterangan**:
  Struktur algoritma *if-else* diterapkan secara tegas untuk mengkategorikan batas umur pengguna maupun film ke dalam 3 kelompok utama:
  
  ```php
  // Algoritma if-else klasifikasi umur:
  if ($age < 13) {
      $category = 'Di Bawah Umur'; // Hanya boleh film Di Bawah Umur (SU)
  } elseif ($age < 18) {
      $category = 'Remaja';        // Boleh film Di Bawah Umur dan Remaja (13+)
  } else {
      $category = 'Dewasa';        // Boleh semua kategori (SU, 13+, 17+, 21+)
  }
  ```
  - **Validasi Akses**: Pengguna yang berstatus *Di Bawah Umur* dicegah secara sistemik untuk menonton, membuka detail, maupun membeli film kategori *Remaja* atau *Dewasa* (HTTP 403 Forbidden).
  - **Tampilan UI**: Kartu film dan dashboard menampilkan teks kategori yang mudah dipahami: `Di Bawah Umur (SU)`, `Remaja (13+)`, dan `Dewasa (18+/21+)`.

---

### 🧾 Pembaruan 4: Bukti Transaksi Resmi (Struk yang Dapat Dicetak & Didownload)
- **File Terdampak**:
  - [database/migrations/2026_10_21_132908_create_transactions_table.php](file:///c:/laragon/www/rekomendasi-film/database/migrations/2026_10_21_132908_create_transactions_table.php)
  - [app/Models/Transaction.php](file:///c:/laragon/www/rekomendasi-film/app/Models/Transaction.php)
  - [app/Http/Controllers/Api/TransactionController.php](file:///c:/laragon/www/rekomendasi-film/app/Http/Controllers/Api/TransactionController.php)
  - [resources/views/user/transactions.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/user/transactions.blade.php)
  - [resources/views/layouts/app.blade.php](file:///c:/laragon/www/rekomendasi-film/resources/views/layouts/app.blade.php)
  - [public/css/app.css](file:///c:/laragon/www/rekomendasi-film/public/css/app.css)
- **Keterangan**:
  1. **Tabel Basis Data `transactions`**:
     - Menyimpan `order_number` unik (misal: `TRX-20261021-XXXX`), `user_id`, `movie_id`, `amount`, `payment_method` (QRIS, BCA VA, Mandiri VA), `status` (`success`), dan `paid_at`.
  2. **Tampilan Struk Transaksi Resmi**:
     - Didesain menyerupai struk pembayaran digital profesional dengan detail No. Transaksi, Tanggal & Jam, Identitas Akun, Judul Film, Kategori Usia, Metode Pembayaran, Stempel `LUNAS / BERHASIL`, dan Total Bayar.
  3. **Fitur Cetak Struk (PDF / Printer)**:
     - Dilengkapi tombol **Cetak Struk (PDF)** menggunakan `window.print()`.
     - Styling khusus `@media print` diterapkan pada `app.css` untuk otomatis menyembunyikan navigasi, tombol, dan background website gelap sehingga hasil cetakan/PDF berlatar putih bersih dengan tipografi tajam.
  4. **Fitur Download Struk (Berkas Teks)**:
     - Dilengkapi tombol **Download Struk (Teks)** yang langsung menghasilkan dan mengunduh berkas `Struk-[NoOrder].txt` ke perangkat pengguna secara instan tanpa reload halaman.
  5. **Halaman Menu Riwayat Transaksi (`/transactions`)**:
     - Menambahkan menu **Transaksi** pada navigasi utama akun pengguna.
     - Pengguna dapat melihat daftar seluruh riwayat pembelian film yang pernah dilakukan dan membuka kembali struk pembayaran untuk dicetak/didownload kapan saja.

---

### 📱 Pembaruan 5: Kesiapan REST API untuk Aplikasi Mobile (Android & iOS)
- **File Terdampak**:
  - [routes/api.php](file:///c:/laragon/www/rekomendasi-film/routes/api.php)
- **Keterangan**:
  - Seluruh endpoint API kini berjumlah **29 routes** aktif dengan dukungan autentikasi Bearer Token (Laravel Sanctum).
  - Penambahan endpoint mobile baru:
    - `POST /api/movies/{id}/purchase` : Beli film dan terbitkan struk transaksi dari perangkat mobile.
    - `GET /api/me/transactions` : Mengambil daftar histori transaksi akun pengguna di mobile.
    - `GET /api/transactions/{identifier}` : Mengambil data struk spesifik berdasarkan nomor order / ID.
