<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $genres = Genre::withCount('movies')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $genres]);
    }

    public function store(GenreRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $genre = Genre::create($data);

        return response()->json([
            'message' => 'Genre berhasil ditambahkan.',
            'data' => $genre,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $genre = Genre::withCount('movies')->findOrFail($id);

        return response()->json(['data' => $genre]);
    }

    public function update(GenreRequest $request, int $id): JsonResponse
    {
        $genre = Genre::findOrFail($id);
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $genre->update($data);

        return response()->json([
            'message' => 'Genre berhasil diperbarui.',
            'data' => $genre,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();

        return response()->json([
            'message' => 'Genre berhasil dihapus.',
        ]);
    }
}
