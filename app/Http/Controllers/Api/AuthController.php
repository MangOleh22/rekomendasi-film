<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => strtolower($validated['username']),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? ($validated['username'] . '@local.domain'),
            'password' => Hash::make($validated['password']),
            'birth_date' => $validated['birth_date'],
            'role' => 'user',
            'minimum_rating' => $validated['minimum_rating'] ?? 0.0,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        if (!$request->expectsJson()) {
            Auth::login($user);
        }

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'age' => $user->age,
                'role' => $user->role,
                'minimum_rating' => $user->minimum_rating,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $input = trim($credentials['username']);

        // Cari berdasarkan username atau email
        $user = User::where('username', $input)
            ->orWhere('email', $input)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau kata sandi salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Login session web browser
        Auth::login($user);

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'age' => $user->age,
                'role' => $user->role,
                'minimum_rating' => $user->minimum_rating,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'role' => $user->role,
                'minimum_rating' => (float) $user->minimum_rating,
                'favorite_genres' => $user->favoriteGenres->pluck('id'),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'age' => $user->age,
                'role' => $user->role,
                'minimum_rating' => (float) $user->minimum_rating,
            ],
        ]);
    }
}
