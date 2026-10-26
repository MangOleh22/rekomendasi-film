<?php

namespace App\Support;

use App\Models\Movie;
use App\Models\User;

/**
 * Evaluasi batasan usia: pengguna (Di Bawah Umur < 13, Remaja 13-17, Dewasa >= 18)
 * hanya boleh mengakses film pada kategori yang sama atau lebih rendah.
 */
class AgeAccess
{
    public static function category(int $age): string
    {
        if ($age < 13) {
            return 'Di Bawah Umur';
        }

        return $age < 18 ? 'Remaja' : 'Dewasa';
    }

    public static function evaluate(User $user, Movie $movie): array
    {
        $userCategory = self::category((int) $user->age);
        $movieCategory = self::category((int) $movie->age_limit);

        $isAllowed = match ($userCategory) {
            'Dewasa' => true,
            'Remaja' => $movieCategory !== 'Dewasa',
            default => $movieCategory === 'Di Bawah Umur',
        };

        return [
            'is_allowed' => $isAllowed,
            'user_category' => $userCategory,
            'movie_category' => $movieCategory,
            'user_age' => (int) $user->age,
        ];
    }
}
