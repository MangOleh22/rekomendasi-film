<?php

use App\Http\Controllers\Api\Admin\GenreController as AdminGenreController;
use App\Http\Controllers\Api\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Api\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

// Auth publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Endpoint dengan autentikasi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);

    // Genre & Preferensi
    Route::get('/genres', [GenreController::class, 'index']);
    Route::get('/me/favorite-genres', [GenreController::class, 'favoriteGenres']);
    Route::put('/me/favorite-genres', [GenreController::class, 'updateFavoriteGenres']);

    // Film & Riwayat
    Route::get('/movies', [MovieController::class, 'index']);
    Route::get('/movies/search', [MovieController::class, 'search']);
    Route::get('/movies/{id}', [MovieController::class, 'show']);
    Route::post('/movies/{id}/watch', [MovieController::class, 'watch']);
    Route::get('/me/watch-history', [MovieController::class, 'watchHistory']);

    // Rekomendasi
    Route::get('/recommendations', [RecommendationController::class, 'index']);

    // Admin API
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminStatsController::class, 'index']);
        Route::apiResource('movies', AdminMovieController::class);
        Route::apiResource('genres', AdminGenreController::class);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
    });
});
