# Dokumentasi Arsitektur & Perancangan Sistem RekomFilm

Dokumen ini memuat spesifikasi teknis lengkap, pemodelan data (ERD & LRS), diagram alur kerja (Flowchart), Use Case, dan Activity Diagram untuk sistem rekomendasi film berbasis **Laravel 11**.

---

## 1. Versi & Spesifikasi Teknologi

| Komponen | Spesifikasi / Versi | Detail Teknis |
|---|---|---|
| **Bahasa Pemrograman** | PHP 8.3.22 | Fitur: strict typing, match expression, readonly classes |
| **Framework Web** | Laravel 11.57.0 | Pola MVC tipis, Service Layer, Form Request, Sanctum |
| **Basis Data** | MySQL 8.0.30 / MariaDB | Relasi cascadeOnDelete, indeks majemuk usia & rating |
| **Autentikasi** | Session Web & Sanctum Token | Kredensial berbasis `username` unik dan password ter-hash |
| **Desain Antarmuka** | Vanilla CSS (Gaya shadcn/ui) | Tema gelap bernuansa zinc, radius sedang, border halus |
| **JavaScript** | Vanilla JS + jQuery 3.7.1 | Manipulasi DOM, debounce pencarian, interaksi modal |
| **Ikonografi** | Ikon Vektor SVG (Lucide) | Murni berkas/vektor SVG, tanpa emotikon |
| **PWA (Progressive Web App)** | Manifest & Service Worker | Cache-first aset statis, pengabaian cache API personal |

---

## 2. Entity Relationship Diagram (ERD)

Diagram pemodelan basis data konseptual dan relasi kardinalitas antar entitas:

```mermaid
erDiagram
    USERS ||--o{ USER_FAVORITE_GENRES : "memilih"
    USERS ||--o{ WATCH_HISTORIES : "menonton"
    MOVIES ||--o{ WATCH_HISTORIES : "dicatat_dalam"
    MOVIES ||--o{ GENRE_MOVIE : "memiliki"
    GENRES ||--o{ GENRE_MOVIE : "dikelompokkan_ke"
    GENRES ||--o{ USER_FAVORITE_GENRES : "difavoritkan_oleh"

    USERS {
        bigint id PK
        string username UK
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
        string slug UK
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
        string slug UK
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
        +id : PK
        username : Unique
        name : String
        password : Hash
        birth_date : Date
        role : "admin" | "user"
        minimum_rating : Decimal
    }

    class GENRES {
        +id : PK
        name : String
        slug : Unique
    }

    class MOVIES {
        +id : PK
        title : String
        slug : Unique
        year : Integer
        rating : Decimal
        age_limit : TinyInt "0, 13, 17, 21"
        is_active : Boolean
    }

    class GENRE_MOVIE {
        +id : PK
        #movie_id : FK
        #genre_id : FK
    }

    class USER_FAVORITE_GENRES {
        +id : PK
        #user_id : FK
        #genre_id : FK
    }

    class WATCH_HISTORIES {
        +id : PK
        #user_id : FK
        #movie_id : FK
        watched_at : Timestamp
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
    Start(["Permintaan Rekomendasi User"]) --> GetUser["Ambil Usia & Rating Min Pengguna"]
    GetUser --> FilterAge["Penyaringan Ketat: age_limit <= user.age"]
    FilterAge --> FilterRating["Penyaringan: rating >= user.minimum_rating"]
    FilterRating --> FilterActive["Penyaringan: is_active = true"]
    FilterActive --> ExcludeWatched["Keluarkan Film dari Riwayat Tonton (watch_histories)"]
    
    ExcludeWatched --> CheckColdStart{"Pengguna Memiliki Riwayat Tonton atau Genre Favorit?"}
    
    CheckColdStart -- "Tidak (Kasus Cold Start)" --> ColdStartSort["Urutkan Kandidat Film Berdasarkan Rating Tertinggi"]
    ColdStartSort --> TakeLimit["Ambil N Teratas (Limit 20 Film)"]
    
    CheckColdStart -- "Ya" --> CalcWeights["Hitung Bobot Genre Tiap g: w(g)<br/>Jika Ada Riwayat: 0.7 * h(g) + 0.3 * f(g)<br/>Jika Tanpa Riwayat: f(g)"]
    CalcWeights --> CalcScore["Hitung Skor Rekomendasi Film:<br/>Afinitas = Rata-rata w(g) pada film<br/>Jaccard = |Genre Film ∩ Favorit| / |Genre Film ∪ Favorit|<br/>Rating Normalisasi = rating / 10<br/>Skor = 100 * (0.5*Afinitas + 0.3*Jaccard + 0.2*Rating_n)"]
    CalcScore --> FilterPositive["Saring Skor > 0"]
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
        U(("Pengguna"))
        A(("Administrator"))
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
stateDiagram-v2
    [*] --> BukaAplikasi
    BukaAplikasi --> InputKredensial : Belum Login
    InputKredensial --> ValidasiLogin
    ValidasiLogin --> Dashboard : Berhasil
    ValidasiLogin --> InputKredensial : Gagal (Pesan Kesalahan)
    
    Dashboard --> TampilRekomendasi : Kalkulasi Rekomendasi Sesuai Usia
    
    state TampilRekomendasi {
        [*] --> TelusuriFilm
        TelusuriFilm --> BukaModalDetail : Klik 'Detail'
        TelusuriFilm --> KlikTonton : Klik 'Tonton'
    }
    
    BukaModalDetail --> KirimWatchHistory : Klik 'Tandai Sudah Ditonton'
    KlikTonton --> KirimWatchHistory
    
    KirimWatchHistory --> SimpanWatchHistory : POST /api/movies/{id}/watch
    SimpanWatchHistory --> UpdateRekomendasi : Refresh Rekomendasi Realtime
    UpdateRekomendasi --> [*]
```

### 6.2 Aktivitas Administrator: Mengelola Data Film & Batas Usia LSF

```mermaid
stateDiagram-v2
    [*] --> BukaPanelAdmin
    BukaPanelAdmin --> MasukMenuFilm : Akses /admin/movies
    MasukMenuFilm --> KlikTambahFilm : Buka Dialog Form
    
    state FormTambahFilm {
        [*] --> InputMetadata : Judul, Tahun, Rating, Sinopsis
        InputMetadata --> PilihKlasifikasiLSF : SU / 13+ / 17+ / 21+
        PilihKlasifikasiLSF --> PilihMultiGenre : Centang Kategori Genre
        PilihMultiGenre --> UploadPoster : Unggah Berkas Poster (Opsional)
    }
    
    FormTambahFilm --> SubmitForm : Klik 'Simpan Film'
    SubmitForm --> ValidasiServer : FormRequest MovieRequest
    
    state ValidasiServer {
        [*] --> PeriksaInput
        PeriksaInput --> SimpanKeDatabase : Lolos Validasi
        PeriksaInput --> TampilkanError : Gagal (Input Tidak Sah)
    }
    
    SimpanKeDatabase --> UpdateTabelAdmin : Perbarui Tabel Admin Realtime
    UpdateTabelAdmin --> [*]
```
