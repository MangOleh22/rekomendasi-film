<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dataset CSV untuk machine learning (semua genre jadi satu):
 * 1 baris = 1 pengguna yang punya riwayat tonton, berisi usia, rating minimum,
 * genre favorit (kolom fav_<slug> 0/1), jumlah tontonan per genre (watch_<slug>)
 * dan genre yang paling sering ditonton (top_genre). Data dianonimkan.
 */
class ExportController extends Controller
{
    /** Ringkasan untuk kartu di panel admin. */
    public function summary(): JsonResponse
    {
        return response()->json(['data' => [
            'rows' => User::has('watchHistories')->count(),
            'genres' => Genre::count(),
        ]]);
    }

    public function ml(): StreamedResponse
    {
        $genres = Genre::orderBy('slug')->get(['id', 'slug']);

        $users = User::has('watchHistories')
            ->with(['favoriteGenres:id', 'watchedMovies' => fn ($q) => $q->with('genres:id')])
            ->orderBy('id')->get();

        $header = ['user_id', 'age', 'age_category', 'minimum_rating', 'total_watch', 'top_genre'];
        foreach ($genres as $g) $header[] = 'fav_' . $g->slug;
        foreach ($genres as $g) $header[] = 'watch_' . $g->slug;
        $header[] = 'favorite_genres';

        $filename = 'dataset-ml-rekomfilm-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($users, $genres, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, $header);
            foreach ($users as $u) {
                $fav = $u->favoriteGenres->pluck('id')->flip();
                $watch = [];
                foreach ($u->watchedMovies as $m) {
                    foreach ($m->genres as $g) $watch[$g->id] = ($watch[$g->id] ?? 0) + 1;
                }
                arsort($watch);
                $topId = array_key_first($watch);

                $row = [$u->id, $u->age, $u->age_category, $u->minimum_rating, $u->watchedMovies->count(),
                    $topId ? $genres->firstWhere('id', $topId)?->slug : ''];
                foreach ($genres as $g) $row[] = isset($fav[$g->id]) ? 1 : 0;
                foreach ($genres as $g) $row[] = $watch[$g->id] ?? 0;
                $row[] = $genres->whereIn('id', $u->favoriteGenres->pluck('id'))->pluck('slug')->implode('|');
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
