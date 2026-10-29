<?php

namespace Tests\Feature;

use App\Events\MovieChanged;
use App\Models\Movie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Siaran realtime (Pusher/Reverb): aman, tidak membocorkan data, dan tidak boleh merusak aplikasi. */
class RealtimeTest extends TestCase
{
    use RefreshDatabase;

    private const ENV_NAMES = [
        'BROADCAST_CONNECTION', 'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
        'REVERB_APP_ID', 'REVERB_APP_KEY', 'REVERB_APP_SECRET', 'REVERB_HOST',
    ];

    /** Nilai `default` di config/broadcasting.php untuk kombinasi .env tertentu (env dipulihkan setelahnya). */
    private function defaultDriver(array $env): string
    {
        $old = [];
        foreach (self::ENV_NAMES as $n) {
            $old[$n] = [$_ENV[$n] ?? null, $_SERVER[$n] ?? null, getenv($n)];
        }

        try {
            foreach (self::ENV_NAMES as $n) {
                $v = $env[$n] ?? '';
                $_ENV[$n] = $_SERVER[$n] = $v;
                putenv("{$n}={$v}");
            }

            return (require config_path('broadcasting.php'))['default'];
        } finally {
            foreach ($old as $n => [$e, $s, $g]) {
                if ($e === null) {
                    unset($_ENV[$n]);
                } else {
                    $_ENV[$n] = $e;
                }
                if ($s === null) {
                    unset($_SERVER[$n]);
                } else {
                    $_SERVER[$n] = $s;
                }
                putenv($g === false ? $n : "{$n}={$g}");
            }
        }
    }

    private function pusherEnv(array $override = []): array
    {
        return array_merge([
            'BROADCAST_CONNECTION' => 'pusher', 'PUSHER_APP_ID' => '123', 'PUSHER_APP_KEY' => 'k',
            'PUSHER_APP_SECRET' => 's', 'PUSHER_APP_CLUSTER' => 'ap1',
        ], $override);
    }

    public function test_event_goes_to_public_movies_channel_with_only_action_and_id(): void
    {
        $e = new MovieChanged('updated', 5);

        $channels = $e->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertNotInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('movies', $channels[0]->name);
        $this->assertSame('movie.changed', $e->broadcastAs());

        // Channel publik => tidak boleh memuat judul (film bisa nonaktif / dibatasi umur).
        $this->assertSame(['action' => 'updated', 'id' => 5], get_object_vars($e));
    }

    public function test_broadcast_failure_does_not_break_the_admin_action(): void
    {
        Event::listen(MovieChanged::class, fn () => throw new \RuntimeException('Pusher tidak terjangkau'));

        app('auth')->forgetGuards();
        $admin = User::factory()->create(['role' => 'admin', 'birth_date' => Carbon::now()->subYears(30)->toDateString()]);
        $this->seed();
        $m = Movie::has('genres')->first();

        $this->withHeaders(['Authorization' => 'Bearer ' . $admin->createToken('t')->plainTextToken])
            ->putJson("/api/admin/movies/{$m->id}", [
                'title' => $m->title, 'year' => $m->year, 'rating' => $m->rating, 'age_limit' => $m->age_limit, 'genres_sent' => 1,
            ])->assertOk();
    }

    public function test_admin_change_dispatches_event_with_movie_id(): void
    {
        Event::fake([MovieChanged::class]);
        $this->seed();
        $m = Movie::first();

        MovieChanged::for('deleted', $m);

        Event::assertDispatched(MovieChanged::class, fn ($e) => $e->action === 'deleted' && $e->id === $m->id);
    }

    public function test_complete_pusher_keys_keep_pusher_driver(): void
    {
        $this->assertSame('pusher', $this->defaultDriver($this->pusherEnv()));
    }

    public function test_incomplete_pusher_keys_fall_back_to_null_instead_of_crashing(): void
    {
        foreach (['PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER'] as $missing) {
            $this->assertSame('null', $this->defaultDriver($this->pusherEnv([$missing => ''])), "tanpa {$missing}");
        }
    }

    public function test_incomplete_reverb_keys_fall_back_to_null(): void
    {
        $this->assertSame('null', $this->defaultDriver(['BROADCAST_CONNECTION' => 'reverb']));
        $this->assertSame('reverb', $this->defaultDriver([
            'BROADCAST_CONNECTION' => 'reverb', 'REVERB_APP_ID' => 'a', 'REVERB_APP_KEY' => 'b',
            'REVERB_APP_SECRET' => 'c', 'REVERB_HOST' => 'localhost',
        ]));
    }

    public function test_log_and_null_drivers_are_untouched(): void
    {
        $this->assertSame('log', $this->defaultDriver(['BROADCAST_CONNECTION' => 'log']));
        $this->assertSame('null', $this->defaultDriver(['BROADCAST_CONNECTION' => 'null']));
        $this->assertSame('null', $this->defaultDriver([]));
    }

    public function test_pusher_client_has_short_timeouts_so_outage_cannot_hang_admin(): void
    {
        $opts = config('broadcasting.connections.pusher.client_options');

        $this->assertLessThanOrEqual(5, $opts['timeout']);
        $this->assertLessThanOrEqual(5, $opts['connect_timeout']);
    }
}
