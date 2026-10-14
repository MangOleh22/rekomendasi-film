# Dokumentasi Arsitektur & Perancangan Sistem RekomFilm

Dokumen ini memuat spesifikasi teknis lengkap, pemodelan data (ERD & LRS), diagram alur kerja (Flowchart), Use Case, dan Activity Diagram untuk sistem rekomendasi film berbasis **Laravel 11**.

---

## 1. Versi & Spesifikasi Teknologi

| Komponen | Spesifikasi / Versi | Detail Teknis |
|---|---|---|
| **Versi Rilis Aplikasi** | v1.0.0 (Rilis Awal) | Semantic versioning |
| **Bahasa Pemrograman** | PHP >= 8.2 s.d. 8.4+ | Teruji di PHP 8.3 bawaan Laragon, kompatibel PHP 8.2 dan 8.4+ |
| **Framework Web** | Laravel 11.57.0 | Pola MVC, Service Layer, Form Request, Sanctum |
| **Basis Data** | MySQL 8.0.30 / MariaDB | Relasi cascadeOnDelete, indeks majemuk usia & rating |
| **Autentikasi** | Session Web & Sanctum Token | Kredensial berbasis `username` unik dan password ter-hash |
| **Desain Antarmuka** | Vanilla CSS (Gaya shadcn/ui) | Tema gelap netral zinc, radius sedang, border halus |
| **JavaScript** | Vanilla JS + jQuery 3.7.1 | Manipulasi DOM, debounce pencarian, interaksi modal |
| **Ikonografi** | Ikon Vektor SVG (Lucide) | Murni berkas/vektor SVG, tanpa emotikon |
| **PWA (Progressive Web App)** | Manifest & Service Worker | Cache-first aset statis, pengabaian cache API personal |

---

## 2. Entity Relationship Diagram (ERD)

Diagram pemodelan basis data konseptual dan relasi kardinalitas antar entitas:

```mermaid
erDiagram
    USERS ||--o{ USER_FAVORITE_GENRES : chooses
    USERS ||--o{ WATCH_HISTORIES : watches
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
        tinyint age_limit
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

    USERS "1" --> "0..*" USER_FAVORITE_GENRES : has
    GENRES "1" --> "0..*" USER_FAVORITE_GENRES : belongs_to
    MOVIES "1" --> "0..*" GENRE_MOVIE : classified_in
    GENRES "1" --> "0..*" GENRE_MOVIE : groups
    USERS "1" --> "0..*" WATCH_HISTORIES : logs
    MOVIES "1" --> "0..*" WATCH_HISTORIES : recorded_in
```

---

## 4. Flowchart Logika Rekomendasi & Filter Usia LSF

Alur kerja evaluasi dan pembobotan film pada `RecommendationService`:

```mermaid
flowchart TD
    Start(["Permintaan Rekomendasi User"]) --> GetUser["Ambil Usia dan Rating Min Pengguna"]
    GetUser --> FilterAge["Penyaringan Ketat: age_limit <= user.age"]
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
    
    TakeLimit --> ReturnResponse(["Kembalikan Data Rekomendasi ke Antarmuka"])
```

---

## 5. Use Case Diagram

Interaksi antara Aktor (**Pengguna** dan **Administrator**) terhadap kapabilitas sistem:

```mermaid
flowchart LR
    subgraph Aktor
        U["Pengguna"]
        A["Administrator"]
    end

    subgraph "Sistem RekomFilm"
        UC1["Masuk / Daftar Akun (Username & Tanggal Lahir)"]
        UC2["Lihat Rekomendasi Film (Sesuai Usia LSF)"]
        UC3["Eksplorasi Katalog & Cari Film (Debounce)"]
        UC4["Tandai Film Sudah Ditonton"]
        UC5["Lihat Riwayat Tontonan"]
        UC6["Atur Preferensi (Genre Favorit & Rating Min)"]
        UC7["Kelola CRUD Film & Unggah Poster"]
        UC8["Kelola Kategori Genre"]
        UC9["Kelola Akun & Peran Pengguna"]
        UC10["Lihat Statistik Ringkasan Sistem"]
    end

    U --> UC1
    U --> UC2
    U --> UC3
    U --> UC4
    U --> UC5
    U --> UC6

    A --> UC1
    A --> UC7
    A --> UC8
    A --> UC9
    A --> UC10
```

---

## 6. Activity Diagram

### 6.1 Aktivitas Pengguna: Eksplorasi, Tonton, dan Pembaruan Rekomendasi

```mermaid
flowchart TD
    Start(["Mulai"]) --> BukaAplikasi["Buka Halaman Web"]
    BukaAplikasi --> CekLogin{"Sudah Login?"}
    CekLogin -- "Belum" --> InputKredensial["Input Username dan Password"]
    InputKredensial --> SubmitLogin["Kirim Form Login"]
    SubmitLogin --> ValidasiAuth{"Kredensial Valid?"}
    ValidasiAuth -- "Tidak" --> MunculError["Tampilkan Pesan Kesalahan"] --> InputKredensial
    ValidasiAuth -- "Ya" --> MasukDashboard["Masuk Dashboard"]
    CekLogin -- "Sudah" --> MasukDashboard

    MasukDashboard --> HitungRekomendasi["Sistem Memproses Filter Usia dan Bobot"]
    HitungRekomendasi --> TampilDaftar["Tampilkan Daftar Rekomendasi"]
    
    TampilDaftar --> PilihAksi{"Pilih Tindakan"}
    PilihAksi --> KlikDetail["Buka Modal Detail Film"]
    PilihAksi --> KlikTonton["Klik Tombol Tonton"]
    
    KlikDetail --> KonfirmasiTonton["Klik Tandai Sudah Ditonton"] --> CatatHistory["Simpan ke Riwayat Tonton"]
    KlikTonton --> CatatHistory
    
    CatatHistory --> RefreshRekomendasi["Refresh Rekomendasi Otomatis"]
    RefreshRekomendasi --> Selesai(["Selesai"])
```

### 6.2 Aktivitas Administrator: Mengelola Data Film & Batas Usia LSF

```mermaid
flowchart TD
    Start(["Mulai"]) --> LoginAdmin["Login sebagai Administrator"]
    LoginAdmin --> BukaPanel["Akses Panel Admin /admin"]
    BukaPanel --> MenuFilm["Pilih Menu Kelola Film"]
    MenuFilm --> BukaModal["Klik Tambah Film Baru"]
    
    BukaModal --> InputForm["Isi Judul, Tahun, Rating, Sinopsis"]
    InputForm --> PilihLSF["Tentukan Klasifikasi Usia LSF (SU, 13+, 17+, 21+)"]
    PilihLSF --> CentangGenre["Pilih Relasi Multi-Genre"]
    CentangGenre --> SubmitForm["Kirim Formulir (POST /api/admin/movies)"]
    
    SubmitForm --> ValidasiInput{"Validasi Form Request Berhasil?"}
    ValidasiInput -- "Tidak" --> MunculValidasiError["Tampilkan Pesan Error Validasi"] --> InputForm
    ValidasiInput -- "Ya" --> SimpanDB["Simpan Data Film ke Database"]
    
    SimpanDB --> UpdateTabel["Perbarui Tabel Katalog Admin"]
    UpdateTabel --> Selesai(["Selesai"])
```
