<?php

namespace App\Events;

use App\Models\Movie;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Disiarkan ke channel publik "movies" saat admin menambah/mengubah/menghapus film.
 * Memakai ShouldBroadcastNow agar terkirim langsung tanpa queue worker.
 * Payload SENGAJA hanya aksi + id (tanpa judul): channel ini publik, sedangkan film bisa nonaktif / dibatasi umur.
 */
class MovieChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public string $action, public int $id) {}

    public static function for(string $action, Movie $movie): void
    {
        // Gagal siaran (mis. Pusher tak terjangkau) tidak boleh menggagalkan aksi admin.
        try {
            static::dispatch($action, $movie->id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function broadcastOn(): array
    {
        return [new Channel('movies')];
    }

    public function broadcastAs(): string
    {
        return 'movie.changed';
    }
}
