<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use App\Models\WatchHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Admin
        $admin = User::firstOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'birth_date' => $now->copy()->subYears(30)->toDateString(),
            'role' => 'admin',
            'minimum_rating' => 0.0,
        ]);

        // 2. User Dewasa (22 tahun)
        $dewasa = User::firstOrCreate([
            'username' => 'dewasa',
        ], [
            'name' => 'Budi Santoso',
            'email' => 'dewasa@example.com',
            'password' => Hash::make('password'),
            'birth_date' => $now->copy()->subYears(22)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 7.0,
        ]);

        // 3. User Remaja (15 tahun)
        $remaja = User::firstOrCreate([
            'username' => 'remaja',
        ], [
            'name' => 'Siti Aulia',
            'email' => 'remaja@example.com',
            'password' => Hash::make('password'),
            'birth_date' => $now->copy()->subYears(15)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 6.0,
        ]);

        // 4. User Anak (12 tahun)
        $anak = User::firstOrCreate([
            'username' => 'anak',
        ], [
            'name' => 'Doni Cilik',
            'email' => 'anak@example.com',
            'password' => Hash::make('password'),
            'birth_date' => $now->copy()->subYears(12)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 5.0,
        ]);

        // Pasang genre favorit
        $scifi = Genre::where('name', 'Sci-Fi')->first();
        $aksi = Genre::where('name', 'Aksi')->first();
        $animasi = Genre::where('name', 'Animasi')->first();

        if ($scifi && $aksi) {
            $dewasa->favoriteGenres()->syncWithoutDetaching([$scifi->id, $aksi->id]);
        }

        if ($animasi) {
            $anak->favoriteGenres()->syncWithoutDetaching([$animasi->id]);
        }

        // Catat riwayat tonton untuk Budi (dewasa): Inception
        $inception = Movie::where('title', 'Inception')->first();
        if ($inception) {
            WatchHistory::firstOrCreate([
                'user_id' => $dewasa->id,
                'movie_id' => $inception->id,
            ], [
                'watched_at' => $now->copy()->subDays(2),
            ]);
        }
    }
}
