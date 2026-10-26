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
        $users = User::select('id', 'username', 'name', 'email', 'birth_date', 'role', 'is_active', 'minimum_rating', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage($request, 15));

        return response()->json($users);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['sometimes', 'required', 'in:user,admin'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date', 'before:today'],
            'minimum_rating' => ['sometimes', 'required', 'numeric', 'min:0', 'max:10'],
        ]);

        // Admin tidak boleh mengunci dirinya sendiri.
        if ($user->id === $request->user()->id && (($validated['role'] ?? 'admin') !== 'admin' || ($validated['is_active'] ?? true) === false)) {
            return response()->json(['message' => 'Anda tidak dapat menurunkan atau menonaktifkan akun sendiri.'], 422);
        }

        $user->update($validated);

        if (array_key_exists('is_active', $validated) && !$validated['is_active']) {
            $user->tokens()->delete(); // paksa keluar dari semua perangkat
        }

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->tokens()->delete();
        $user->delete(); // riwayat tonton, transaksi, dan keranjang ikut terhapus (cascade)

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }
}
