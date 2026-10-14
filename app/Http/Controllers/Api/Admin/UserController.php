<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::select('id', 'username', 'name', 'email', 'birth_date', 'role', 'minimum_rating', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($users);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['sometimes', 'required', 'in:user,admin'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date', 'before:today'],
            'minimum_rating' => ['sometimes', 'required', 'numeric', 'min:0', 'max:10'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $user,
        ]);
    }
}
