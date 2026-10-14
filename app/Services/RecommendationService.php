<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Menghitung rekomendasi film untuk user berdasarkan usia, preferensi, riwayat tonton, dan rating.
     *
     * ponytail: optimasi cache & pre-filtering SQL untuk jutaan baris, tambahkan saat katalog skala produksi besar.
     */
    public function forUser(User $user, int $limit = 20): Collection
    {
        $favoriteIds = $user->favoriteGenres()->pluck('genres.id')->all();

        // Hitung tontonan per genre
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
            ->with('genres:id,name,slug')
            ->where('is_active', true)
            ->forAge($user->age)
            ->minRating((float) $user->minimum_rating)
            ->whereNotIn('id', $watchedIds)
            ->get();

        // Cold start check: jika belum ada favorit dan belum ada riwayat
        $isColdStart = empty($favoriteIds) && !$hasHistory;

        if ($isColdStart) {
            return $movies
                ->sortByDesc('rating')
                ->take($limit)
                ->map(function (Movie $movie) {
                    $movie->score = round(($movie->rating / 10) * 100, 2);
                    return $movie;
                })
                ->values();
        }

        $scored = $movies->map(function (Movie $movie) use ($weights, $favoriteIds) {
            $genreIds = $movie->genres->pluck('id');

            $affinity = $genreIds->isEmpty()
                ? 0
                : (float) $genreIds->avg(fn ($id) => $weights[$id] ?? 0);

            $union = $genreIds->merge($favoriteIds)->unique()->count();
            $inter = $genreIds->intersect($favoriteIds)->count();
            $jaccard = $union > 0 ? (float) ($inter / $union) : 0.0;

            $ratingNormalized = (float) ($movie->rating / 10);

            $score = 100 * (
                0.5 * $affinity +
                0.3 * $jaccard +
                0.2 * $ratingNormalized
            );

            $movie->score = round($score, 2);
            return $movie;
        });

        // Urutkan berdasarkan skor tertinggi, lalu rating jika seri
        $ranked = $scored
            ->filter(fn ($movie) => $movie->score > 0)
            ->sort(function ($a, $b) {
                if ($a->score == $b->score) {
                    return $b->rating <=> $a->rating;
                }
                return $b->score <=> $a->score;
            })
            ->take($limit)
            ->values();

        // Jika tidak ada hasil berskor > 0, fallback ke film rating tertinggi lolos filter usia
        if ($ranked->isEmpty()) {
            return $movies
                ->sortByDesc('rating')
                ->take($limit)
                ->map(function (Movie $movie) {
                    $movie->score = round(($movie->rating / 10) * 100, 2);
                    return $movie;
                })
                ->values();
        }

        return $ranked;
    }
}
