<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Halaman Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Halaman Pengguna Terautentikasi (Web Session)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('user.dashboard');
    })->name('dashboard');

    Route::get('/history', function () {
        return view('user.history');
    })->name('history');

    Route::get('/transactions', function () {
        return view('user.transactions');
    })->name('transactions');

    Route::get('/preferences', function () {
        return view('user.preferences');
    })->name('preferences');

    // Halaman Admin (Role: Admin)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/movies', function () {
            return view('admin.movies');
        })->name('admin.movies');

        Route::get('/genres', function () {
            return view('admin.genres');
        })->name('admin.genres');

        Route::get('/users', function () {
            return view('admin.users');
        })->name('admin.users');
    });
});
