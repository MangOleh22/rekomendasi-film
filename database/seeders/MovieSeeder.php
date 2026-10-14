<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $genres = Genre::all()->keyBy('name');

        $movies = [
            // Klasifikasi SU (0)
            [
                'title' => 'Petualangan Sherina 2',
                'year' => 2023,
                'rating' => 8.2,
                'age_limit' => 0,
                'synopsis' => 'Sherina dan Sadam yang telah dewasa bertemu kembali di Kalimantan untuk liputan dan penyelamatan orangutan.',
                'genres' => ['Petualangan', 'Keluarga', 'Komedi'],
            ],
            [
                'title' => 'Laskar Pelangi',
                'year' => 2008,
                'rating' => 8.5,
                'age_limit' => 0,
                'synopsis' => 'Kisah perjuangan 10 anak di desa Gantong, Belitung, dalam menempuh pendidikan di sekolah sederhana.',
                'genres' => ['Drama', 'Keluarga', 'Petualangan'],
            ],
            [
                'title' => 'Spirited Away',
                'year' => 2001,
                'rating' => 8.6,
                'age_limit' => 0,
                'synopsis' => 'Seorang gadis muda tanpa sengaja masuk ke dunia roh magis dan berusaha menyelamatkan kedua orangtuanya.',
                'genres' => ['Animasi', 'Petualangan', 'Fantasi', 'Keluarga'],
            ],
            [
                'title' => 'Toy Story 3',
                'year' => 2010,
                'rating' => 8.3,
                'age_limit' => 0,
                'synopsis' => 'Woody, Buzz, dan mainan lainnya secara keliru didonasikan ke pusat penitipan anak saat Andy bersiap kuliah.',
                'genres' => ['Animasi', 'Petualangan', 'Komedi', 'Keluarga'],
            ],
            [
                'title' => 'Keluarga Cemara',
                'year' => 2019,
                'rating' => 7.8,
                'age_limit' => 0,
                'synopsis' => 'Sebuah keluarga kaya mendadak jatuh miskin dan harus beradaptasi hidup sederhana di sebuah desa kecil.',
                'genres' => ['Drama', 'Keluarga'],
            ],

            // Klasifikasi 13+ (13)
            [
                'title' => 'Inception',
                'year' => 2010,
                'rating' => 8.8,
                'age_limit' => 13,
                'synopsis' => 'Seorang pencuri yang menyusup ke alam bawah sadar targetnya diberi tugas menanamkan ide ke dalam pikiran pewaris korporat.',
                'genres' => ['Aksi', 'Sci-Fi', 'Petualangan'],
            ],
            [
                'title' => 'Interstellar',
                'year' => 2014,
                'rating' => 8.7,
                'age_limit' => 13,
                'synopsis' => 'Sekelompok penjelajah melintasi lubang cacing di luar angkasa demi memastikan kelangsungan hidup umat manusia.',
                'genres' => ['Sci-Fi', 'Drama', 'Petualangan'],
            ],
            [
                'title' => 'Spider-Man: Into the Spider-Verse',
                'year' => 2018,
                'rating' => 8.4,
                'age_limit' => 13,
                'synopsis' => 'Miles Morales remaja menjadi Spider-Man baru dan bekerja sama dengan pahlawan dari dimensi paralel.',
                'genres' => ['Animasi', 'Aksi', 'Sci-Fi', 'Petualangan'],
            ],
            [
                'title' => 'Dilan 1990',
                'year' => 2018,
                'rating' => 7.6,
                'age_limit' => 13,
                'synopsis' => 'Kisah asmara SMA antara Milea, siswi pindahan asal Jakarta, dan Dilan, anggota geng motor puitis di Bandung.',
                'genres' => ['Romantis', 'Drama'],
            ],
            [
                'title' => 'Habibie & Ainun',
                'year' => 2012,
                'rating' => 7.9,
                'age_limit' => 13,
                'synopsis' => 'Kisah cinta abadi dan dedikasi ilmiah antara B.J. Habibie dan Hasri Ainun Besari demi kemajuan bangsa.',
                'genres' => ['Drama', 'Romantis'],
            ],
            [
                'title' => 'Avengers: Endgame',
                'year' => 2019,
                'rating' => 8.4,
                'age_limit' => 13,
                'synopsis' => 'Para pahlawan Avengers yang tersisa berkumpul kembali demi membalikkan kehancuran yang ditimbulkan Thanos.',
                'genres' => ['Aksi', 'Sci-Fi', 'Petualangan'],
            ],

            // Klasifikasi 17+ (17)
            [
                'title' => 'The Raid: Redemption',
                'year' => 2011,
                'rating' => 7.6,
                'age_limit' => 17,
                'synopsis' => 'Tim pasukan elit terjebak di dalam apartemen kumuh yang dikuasai gembong narkoba kejam dan pasukannya.',
                'genres' => ['Aksi', 'Thriller'],
            ],
            [
                'title' => 'Pengabdi Setan',
                'year' => 2017,
                'rating' => 7.4,
                'age_limit' => 17,
                'synopsis' => 'Setelah ibu mereka meninggal karena sakit aneh selama bertahun-tahun, sebuah keluarga diteror rahasia masa lalu.',
                'genres' => ['Horor', 'Misteri'],
            ],
            [
                'title' => 'The Dark Knight',
                'year' => 2008,
                'rating' => 9.0,
                'age_limit' => 17,
                'synopsis' => 'Batman menghadapi ancaman anarkis psikotik bernama Joker yang menguji batas moral dan keadilan di Gotham.',
                'genres' => ['Aksi', 'Drama', 'Thriller'],
            ],
            [
                'title' => 'Parasite',
                'year' => 2019,
                'rating' => 8.5,
                'age_limit' => 17,
                'synopsis' => 'Keluarga miskin menyusup ke kehidupan keluarga kaya raya dengan berpura-pura menjadi tenaga profesional independen.',
                'genres' => ['Drama', 'Thriller', 'Komedi'],
            ],
            [
                'title' => 'Joker',
                'year' => 2019,
                'rating' => 8.4,
                'age_limit' => 17,
                'synopsis' => 'Arthur Fleck, seorang komedian gagal yang diabaikan masyarakat, perlahan tenggelam dalam kegilaan dan nihilisme.',
                'genres' => ['Drama', 'Thriller'],
            ],

            // Klasifikasi 21+ (21)
            [
                'title' => 'Oppenheimer',
                'year' => 2023,
                'rating' => 8.9,
                'age_limit' => 21,
                'synopsis' => 'Kisah fisikawan J. Robert Oppenheimer dan dilema moral dalam kepemimpinannya pada Proyek Manhattan.',
                'genres' => ['Drama', 'Misteri'],
            ],
            [
                'title' => 'The Wolf of Wall Street',
                'year' => 2013,
                'rating' => 8.2,
                'age_limit' => 21,
                'synopsis' => 'Perjalanan pialang saham Jordan Belfort meraup kekayaan ilegal lewat penipuan korporasi dan pesta liar.',
                'genres' => ['Komedi', 'Drama'],
            ],
            [
                'title' => 'Fight Club',
                'year' => 1999,
                'rating' => 8.8,
                'age_limit' => 21,
                'synopsis' => 'Pekerja kantor pengidap insomnia membentuk klub tarung bawah tanah bersama pembuat sabun eksentrik.',
                'genres' => ['Drama', 'Thriller'],
            ],
            [
                'title' => 'Gone Girl',
                'year' => 2014,
                'rating' => 8.1,
                'age_limit' => 21,
                'synopsis' => 'Ketika seorang wanita mendadak hilang di hari ulang tahun pernikahan, suaminya menjadi tersangka utama.',
                'genres' => ['Drama', 'Misteri', 'Thriller'],
            ],
        ];

        foreach ($movies as $data) {
            $movie = Movie::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'year' => $data['year'],
                'rating' => $data['rating'],
                'age_limit' => $data['age_limit'],
                'synopsis' => $data['synopsis'],
                'is_active' => true,
            ]);

            $genreIds = [];
            foreach ($data['genres'] as $genreName) {
                if (isset($genres[$genreName])) {
                    $genreIds[] = $genres[$genreName]->id;
                }
            }
            $movie->genres()->sync($genreIds);
        }
    }
}
