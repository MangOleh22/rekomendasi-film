<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(): JsonResponse
    {
        $genres = Genre::select('id', 'name', 'slug')
            ->withCount('movies')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $genres,
        ]);
    }

    public function favoriteGenres(Request $request): JsonResponse
    {
        $genres = $request->user()->favoriteGenres()->select('genres.id', 'genres.name', 'genres.slug')->get();

        return response()->json([
            'data' => $genres,
        ]);
    }

    public function updateFavoriteGenres(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'genre_ids' => ['present', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ]);

        $request->user()->favoriteGenres()->sync($validated['genre_ids']);

        return response()->json([
            'message' => 'Genre favorit berhasil diperbarui.',
            'data' => $request->user()->favoriteGenres()->pluck('genres.id'),
        ]);
    }
}
