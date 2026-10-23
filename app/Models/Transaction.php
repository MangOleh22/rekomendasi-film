<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'movie_id',
        'amount',
        'payment_method',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected $appends = ['formatted_amount', 'formatted_paid_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Format nominal rupiah dengan pemisah titik, contoh: RP. 10.000
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'RP. ' . number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedPaidAtAttribute(): string
    {
        return $this->paid_at
            ? Carbon::parse($this->paid_at)->locale('id')->translatedFormat('d M Y, H:i') . ' WIB'
            : '-';
    }
}
