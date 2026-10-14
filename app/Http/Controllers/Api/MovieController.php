<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Movie::query()
            ->with('genres:id,name,slug')
            ->where('is_active', true)
            ->forAge($user->age);

        if ($request->filled('min_rating')) {
            $query->minRating((float) $request->min_rating);
        } else {
            $query->minRating((float) $user->minimum_rating);
        }

        if ($request->filled('genre')) {
            $genreSlug = $request->genre;
            $query->whereHas('genres', function ($q) use ($genreSlug) {
                $q->where('slug', $genreSlug)->orWhere('genres.id', $genreSlug);
            });
        }

        $movies = $query->orderBy('rating', 'desc')
            ->paginate($request->input('per_page', 12));

        return response()->json($movies);
    }

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $term = trim($request->input('q', ''));

        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $movies = Movie::query()
            ->with('genres:id,name,slug')
            ->where('is_active', true)
            ->forAge($user->age)
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('synopsis', 'like', "%{$term}%");
            })
            ->orderBy('rating', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'data' => $movies,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $movie = Movie::with('genres:id,name,slug')->find($id);

        if (!$movie) {
            return response()->json([
                'message' => 'Film tidak ditemukan.',
            ], 404);
        }

        // Validasi ketat batas usia
        if ($movie->age_limit > $user->age) {
            return response()->json([
                'message' => 'Konten film berada di luar batas usia akun Anda.',
            ], 403);
        }

        $isWatched = WatchHistory::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->exists();

        return response()->json([
            'data' => array_merge($movie->toArray(), [
                'is_watched' => $isWatched,
            ]),
        ]);
    }

    public function watch(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json(['message' => 'Film tidak ditemukan.'], 404);
        }

        if ($movie->age_limit > $user->age) {
            return response()->json([
                'message' => 'Tidak dapat mencatat tontonan: film di luar batas usia.',
            ], 403);
        }

        $history = WatchHistory::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'watched_at' => now(),
        ]);

        return response()->json([
            'message' => 'Film berhasil dicatat ke riwayat tonton.',
            'data' => $history,
        ]);
    }

    public function watchHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = WatchHistory::with(['movie' => function ($q) {
            $q->with('genres:id,name,slug');
        }])
            ->where('user_id', $user->id)
            ->orderBy('watched_at', 'desc')
            ->paginate(15);

        return response()->json($history);
    }
}
