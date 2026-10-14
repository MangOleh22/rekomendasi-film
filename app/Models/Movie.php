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
        'age_limit',
        'poster_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rating' => 'float',
            'age_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['age_label'];

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

    public function scopeForAge(Builder $query, int $age): Builder
    {
        return $query->where('age_limit', '<=', $age);
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
}
