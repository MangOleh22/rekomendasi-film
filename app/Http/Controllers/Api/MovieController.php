<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Movie;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WatchHistory;
use App\Support\AgeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    private function evaluateAgeAccess(User $user, Movie $movie): array
    {
        return AgeAccess::evaluate($user, $movie);
    }

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
            ->paginate($this->perPage($request, 12));

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
                $q->whereLike('title', "%{$term}%")
                  ->orWhereLike('synopsis', "%{$term}%");
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
        $movie = Movie::with('genres:id,name,slug')->where('is_active', true)->find($id);

        if (!$movie) {
            return response()->json([
                'message' => 'Film tidak ditemukan.',
            ], 404);
        }

        // Validasi batasan usia menggunakan algoritma if-else
        $access = $this->evaluateAgeAccess($user, $movie);
        if (!$access['is_allowed']) {
            return response()->json([
                'message' => "Konten film berada di luar kategori usia Anda ({$access['user_category']}). Film ini dikhususkan untuk kategori {$access['movie_category']}.",
            ], 403);
        }

        $isWatched = WatchHistory::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->exists();

        // Cek status kepemilikan / pembelian film
        $transaction = Transaction::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->where('status', 'success')
            ->latest()
            ->first();

        $isPurchased = ($movie->price <= 0) || ($transaction !== null);

        return response()->json([
            'data' => array_merge($movie->toArray(), [
                'is_watched' => $isWatched,
                'is_purchased' => $isPurchased,
                'transaction' => $transaction,
                'user_age_category' => $access['user_category'],
                'movie_age_category' => $access['movie_category'],
            ]),
        ]);
    }

    public function watch(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $movie = Movie::where('is_active', true)->find($id);

        if (!$movie) {
            return response()->json(['message' => 'Film tidak ditemukan.'], 404);
        }

        // Validasi batasan usia menggunakan algoritma if-else
        $access = $this->evaluateAgeAccess($user, $movie);
        if (!$access['is_allowed']) {
            return response()->json([
                'message' => "Tidak dapat mencatat tontonan: Akun Anda berkategori {$access['user_category']}, sedangkan film ini berkategori {$access['movie_category']}.",
            ], 403);
        }

        // Film berbayar hanya boleh ditonton setelah dibeli.
        $owned = $movie->price <= 0 || Transaction::where('user_id', $user->id)
            ->where('movie_id', $movie->id)->where('status', 'success')->exists();
        if (!$owned) {
            return response()->json(['message' => 'Film berbayar harus dibeli terlebih dahulu.'], 403);
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

    /**
     * Beli film & terbitkan bukti pembayaran (struk transaksi)
     */
    public function purchase(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $movie = Movie::with('genres:id,name,slug')->where('is_active', true)->find($id);

        if (!$movie) {
            return response()->json(['message' => 'Film tidak ditemukan.'], 404);
        }

        $request->validate(['payment_method' => ['nullable', 'in:' . implode(',', Transaction::METHODS)]]);

        // Validasi usia menggunakan algoritma if-else
        $access = $this->evaluateAgeAccess($user, $movie);
        if (!$access['is_allowed']) {
            return response()->json([
                'message' => "Pembelian dibatasi: Akun Anda berkategori {$access['user_category']}, sedangkan film ini untuk {$access['movie_category']}.",
            ], 403);
        }

        // Cek jika sudah pernah membeli
        $existing = Transaction::where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->where('status', 'success')
            ->first();

        if ($existing) {
            CartItem::where('user_id', $user->id)->where('movie_id', $movie->id)->delete();

            return response()->json([
                'message' => 'Anda sudah memiliki film ini.',
                'transaction' => $existing->load(['user', 'movie']),
            ], 200);
        }

        $paymentMethod = $request->input('payment_method', 'QRIS');

        // Buat nomor order transaksi unik
        $orderNumber = 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $transaction = Transaction::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'amount' => $movie->price,
            'payment_method' => $paymentMethod,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        CartItem::where('user_id', $user->id)->where('movie_id', $movie->id)->delete(); // sudah dibeli, tak perlu di keranjang

        return response()->json([
            'message' => 'Pembayaran berhasil! Struk pembelian telah diterbitkan.',
            'transaction' => $transaction->load(['user', 'movie']),
        ], 201);
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
