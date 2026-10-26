<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Daftar seluruh riwayat transaksi pembelian film akun
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = Transaction::with(['movie' => function ($q) {
            $q->with('genres:id,name,slug');
        }])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage($request, 15));

        return response()->json($transactions);
    }

    /**
     * Detail struk transaksi untuk dicetak atau didownload
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();

        $query = Transaction::with(['movie.genres', 'user']);

        if (is_numeric($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where('order_number', $identifier);
        }

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $transaction = $query->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Bukti transaksi (struk) tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $transaction,
        ]);
    }
}
