<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Movie;
use App\Models\Transaction;
use App\Support\AgeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public const METHODS = Transaction::METHODS;

    /** Isi keranjang + ringkasan (jumlah film & total harga). */
    private function summary(Request $request): array
    {
        $this->pruneUnavailable($request->user()->id);

        $items = CartItem::with('movie.genres:id,name,slug')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();
        $total = (int) $items->sum(fn ($i) => $i->movie->price);

        return [
            'data' => $items,
            'count' => $items->count(),
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
            'methods' => self::METHODS,
        ];
    }

    /** Buang item yang filmnya sudah dinonaktifkan admin atau sudah dimiliki pengguna. */
    private function pruneUnavailable(int $userId): void
    {
        CartItem::where('user_id', $userId)
            ->where(fn ($q) => $q
                ->whereDoesntHave('movie', fn ($m) => $m->where('is_active', true))
                ->orWhereIn('movie_id', Transaction::where('user_id', $userId)->where('status', 'success')->select('movie_id')))
            ->delete();
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->summary($request));
    }

    public function add(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $movie = Movie::where('is_active', true)->find($id);

        if (!$movie) {
            return response()->json(['message' => 'Film tidak ditemukan.'], 404);
        }
        if (!AgeAccess::evaluate($user, $movie)['is_allowed']) {
            return response()->json(['message' => 'Film ini di luar kategori usia akun Anda.'], 403);
        }
        if ($movie->price <= 0) {
            return response()->json(['message' => 'Film gratis tidak perlu dimasukkan ke keranjang.'], 422);
        }
        if (Transaction::where('user_id', $user->id)->where('movie_id', $movie->id)->where('status', 'success')->exists()) {
            return response()->json(['message' => 'Anda sudah memiliki film ini.'], 422);
        }

        CartItem::firstOrCreate(['user_id' => $user->id, 'movie_id' => $movie->id]);

        return response()->json(['message' => 'Film ditambahkan ke keranjang.'] + $this->summary($request), 201);
    }

    public function remove(Request $request, int $id): JsonResponse
    {
        CartItem::where('user_id', $request->user()->id)->where('movie_id', $id)->delete();

        return response()->json(['message' => 'Film dihapus dari keranjang.'] + $this->summary($request));
    }

    /** Bayar semua film di keranjang sekaligus: satu faktur, satu transaksi per film. */
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate(['payment_method' => ['required', 'in:' . implode(',', self::METHODS)]]);
        $user = $request->user();

        $this->pruneUnavailable($user->id);

        $items = CartItem::with('movie')->where('user_id', $user->id)->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong atau film di dalamnya sudah tidak tersedia / sudah Anda miliki.'], 422);
        }

        foreach ($items as $item) {
            if (!AgeAccess::evaluate($user, $item->movie)['is_allowed']) {
                return response()->json(['message' => "\"{$item->movie->title}\" di luar kategori usia akun Anda. Hapus dari keranjang."], 403);
            }
        }

        $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $transactions = DB::transaction(function () use ($items, $user, $data, $invoice) {
            $owned = Transaction::where('user_id', $user->id)->where('status', 'success')
                ->whereIn('movie_id', $items->pluck('movie_id'))->pluck('movie_id');

            $created = $items->reject(fn ($i) => $owned->contains($i->movie_id))->map(fn ($i) => Transaction::create([
                'order_number' => 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'invoice_number' => $invoice,
                'user_id' => $user->id,
                'movie_id' => $i->movie_id,
                'amount' => $i->movie->price,
                'payment_method' => $data['payment_method'],
                'status' => 'success',
                'paid_at' => now(),
            ])->load('movie'));

            CartItem::where('user_id', $user->id)->delete();

            return $created->values();
        });

        $total = (int) $transactions->sum('amount');

        return response()->json([
            'message' => 'Pembayaran berhasil! ' . $transactions->count() . ' film ditambahkan ke koleksi Anda.',
            'invoice_number' => $invoice,
            'count' => $transactions->count(),
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
            'transactions' => $transactions,
        ], 201);
    }
}
