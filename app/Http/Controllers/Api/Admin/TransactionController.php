<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /** Riwayat pembelian seluruh pengguna. Filter: q (nomor order/faktur, judul film, username/nama), method. */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['user:id,username,name', 'movie:id,title,age_limit']);

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->whereLike('order_number', $term)
                    ->orWhereLike('invoice_number', $term)
                    ->orWhereHas('movie', fn ($m) => $m->whereLike('title', $term))
                    ->orWhereHas('user', fn ($u) => $u->whereLike('username', $term)->orWhereLike('name', $term));
            });
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        $revenue = (int) (clone $query)->where('status', 'success')->sum('amount');

        return response()->json(
            $query->orderByDesc('id')->paginate($this->perPage($request, 15))->toArray()
            + ['summary' => ['revenue' => $revenue, 'formatted_revenue' => 'Rp ' . number_format($revenue, 0, ',', '.')]]
        );
    }
}
