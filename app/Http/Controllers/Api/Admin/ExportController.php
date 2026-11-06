<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor dataset CSV untuk machine learning:
 * 1 baris = 1 pengguna yang pernah menonton film bergenre target (default Thriller),
 * berisi fitur usia, rating minimum, genre favorit (0/1) dan jumlah tontonan per genre.
 * Data dianonimkan (tanpa nama/email/username).
 */
class ExportController extends Controller
{
    public function ml(Request $request): StreamedResponse
    {
        $slug = is_string($request->query('genre')) ? $request->query('genre') : 'thriller';
        $target = Genre::where('slug', $slug)->firstOrFail();
        $genres = Genre::orderBy('slug')->get(['id', 'slug']);

        $users = User::whereHas('watchedMovies.genres', fn ($q) => $q->whereKey($target->id))
            ->with(['favoriteGenres:id', 'watchedMovies' => fn ($q) => $q->with('genres:id')])
            ->orderBy('id')->get();

        $header = ['user_id', 'age', 'age_category', 'minimum_rating', 'total_watch', 'target_genre', 'target_watch', 'target_ratio'];
        foreach ($genres as $g) $header[] = 'fav_' . $g->slug;
        foreach ($genres as $g) $header[] = 'watch_' . $g->slug;
        $header[] = 'favorite_genres';

        $filename = 'dataset-ml-' . $target->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($users, $genres, $target, $header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($users as $u) {
                $fav = $u->favoriteGenres->pluck('id')->flip();
                $watch = [];
                foreach ($u->watchedMovies as $m) {
                    foreach ($m->genres as $g) $watch[$g->id] = ($watch[$g->id] ?? 0) + 1;
                }
                $total = $u->watchedMovies->count();
                $targetWatch = $watch[$target->id] ?? 0;

                $row = [$u->id, $u->age, $u->age_category, $u->minimum_rating, $total, $target->slug, $targetWatch,
                    $total ? round($targetWatch / $total, 4) : 0];
                foreach ($genres as $g) $row[] = isset($fav[$g->id]) ? 1 : 0;
                foreach ($genres as $g) $row[] = $watch[$g->id] ?? 0;
                $row[] = $genres->whereIn('id', $u->favoriteGenres->pluck('id'))->pluck('slug')->implode('|');
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
