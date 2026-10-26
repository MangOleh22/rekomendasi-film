<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'year',
        'rating',
        'price',
        'age_limit',
        'poster_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rating' => 'float',
            'price' => 'integer',
            'age_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['age_label', 'age_category', 'formatted_price'];

    protected static function booted(): void
    {
        static::creating(function ($movie) {
            if (empty($movie->slug)) {
                $movie->slug = Str::slug($movie->title) . '-' . Str::random(5);
            }
        });
    }

    public function getAgeLabelAttribute(): string
    {
        return match ($this->age_limit) {
            0 => 'SU',
            13 => '13+',
            17 => '17+',
            21 => '21+',
            default => $this->age_limit . '+',
        };
    }

    /**
     * Algoritma if-else klasifikasi kategori usia film:
     * - Di bawah umur: < 13 tahun (SU)
     * - Remaja: 13 - 17 tahun (13+)
     * - Dewasa: >= 18 tahun (17+, 21+)
     */
    public function getAgeCategoryAttribute(): string
    {
        $limit = (int) $this->age_limit;

        if ($limit < 13) {
            return 'Di Bawah Umur';
        } elseif ($limit < 18) {
            return 'Remaja';
        } else {
            return 'Dewasa';
        }
    }

    /**
     * Format penulisan rupiah menggunakan pemisah titik, semisal RP. 10.000
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = (int) ($this->price ?? 0);
        if ($price <= 0) {
            return 'Gratis';
        }
        return 'Rp ' . number_format($price, 0, ',', '.');
    }

    /**
     * Algoritma if-else batasan umur tontonan
     */
    public function scopeForAge(Builder $query, int $age): Builder
    {
        if ($age < 13) {
            return $query->where('age_limit', '<', 13);
        } elseif ($age < 18) {
            return $query->where('age_limit', '<', 18);
        } else {
            return $query;
        }
    }

    public function scopeMinRating(Builder $query, float $rating): Builder
    {
        return $query->where('rating', '>=', $rating);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
