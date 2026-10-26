<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'birth_date',
        'role',
        'is_active',
        'minimum_rating',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'minimum_rating' => 'float',
        ];
    }

    protected $appends = ['age', 'age_category'];

    public function getAgeAttribute(): int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : 0;
    }

    /**
     * Algoritma if-else klasifikasi kategori umur:
     * - Di bawah umur: < 13 tahun
     * - Remaja: 13 - 17 tahun
     * - Dewasa: >= 18 tahun
     */
    public function getAgeCategoryAttribute(): string
    {
        $age = (int) $this->age;

        if ($age < 13) {
            return 'Di Bawah Umur';
        } elseif ($age < 18) {
            return 'Remaja';
        } else {
            return 'Dewasa';
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function favoriteGenres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'user_favorite_genres')->withTimestamps();
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function watchedMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'watch_histories')->withPivot('watched_at');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
