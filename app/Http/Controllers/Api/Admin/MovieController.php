<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovieRequest;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Movie::with('genres:id,name,slug');

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where('title', 'like', "%{$term}%");
        }

        if ($request->filled('age_limit')) {
            $query->where('age_limit', $request->age_limit);
        }

        $movies = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 10));

        return response()->json($movies);
    }

    public function store(MovieRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['poster', 'genres']);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(5);

        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_path'] = '/storage/' . $path;
        }

        $movie = Movie::create($data);

        if ($request->filled('genres')) {
            $movie->genres()->sync($request->input('genres'));
        }

        return response()->json([
            'message' => 'Film berhasil ditambahkan.',
            'data' => $movie->load('genres'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $movie = Movie::with('genres:id,name,slug')->findOrFail($id);

        return response()->json(['data' => $movie]);
    }

    public function update(MovieRequest $request, int $id): JsonResponse
    {
        $movie = Movie::findOrFail($id);
        $data = $request->safe()->except(['poster', 'genres']);

        if ($request->hasFile('poster')) {
            if ($movie->poster_path && str_starts_with($movie->poster_path, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $movie->poster_path));
            }
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_path'] = '/storage/' . $path;
        }

        $movie->update($data);

        if ($request->has('genres')) {
            $movie->genres()->sync($request->input('genres', []));
        }

        return response()->json([
            'message' => 'Film berhasil diperbarui.',
            'data' => $movie->load('genres'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $movie = Movie::findOrFail($id);

        if ($movie->poster_path && str_starts_with($movie->poster_path, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $movie->poster_path));
        }

        $movie->delete();

        return response()->json([
            'message' => 'Film berhasil dihapus.',
        ]);
    }
}
