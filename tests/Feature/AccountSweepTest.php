<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sapuan semua endpoint untuk akun default (hasil seeder) dan akun yang baru mendaftar.
 * Tujuan: tidak ada respons 5xx, dan endpoint terlarang menjawab 403/404/422.
 */
class AccountSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Login lewat API sungguhan (bukan factory) supaya alur kata sandi ikut teruji. */
    private function loginHeaders(string $username, string $password = 'password'): array
    {
        app('auth')->forgetGuards(); // guard Sanctum menyimpan pengguna request sebelumnya selama satu tes
        $res = $this->postJson('/api/login', ['username' => $username, 'password' => $password])->assertOk();
        app('auth')->forgetGuards();

        return ['Authorization' => 'Bearer ' . $res->json('token')];
    }

    private function sweepUser(array $h, bool $admin = false): void
    {
        $get = fn (string $url) => tap($this->withHeaders($h)->getJson($url), fn ($r) => $this->assertLessThan(500, $r->status(), "GET {$url} -> {$r->status()}"));

        $get('/api/me')->assertOk();
        $get('/api/genres')->assertOk();
        $get('/api/me/favorite-genres')->assertOk();
        $get('/api/movies')->assertOk();
        $get('/api/movies?genre=aksi&per_page=5')->assertOk();
        $get('/api/movies/search?q=a')->assertOk();
        $get('/api/movies/search')->assertOk();
        $get('/api/recommendations')->assertOk();
        $get('/api/cart')->assertOk();
        $get('/api/me/watch-history')->assertOk();
        $get('/api/me/transactions')->assertOk();

        $first = $get('/api/movies')->json('data.0.id');
        if ($first) {
            $get("/api/movies/{$first}")->assertOk();
        }
        $get('/api/movies/999999')->assertStatus(404);
        $get('/api/transactions/TRX-TIDAK-ADA')->assertStatus(404);

        $this->withHeaders($h)->putJson('/api/me/favorite-genres', ['genre_ids' => []])->assertOk();

        foreach (['/api/admin/stats', '/api/admin/movies', '/api/admin/genres', '/api/admin/users', '/api/admin/transactions'] as $url) {
            $r = $this->withHeaders($h)->getJson($url);
            $admin ? $r->assertOk() : $r->assertStatus(403);
        }
    }

    public function test_default_accounts_have_no_errors_on_any_endpoint(): void
    {
        foreach (['admin' => true, 'dewasa' => false, 'remaja' => false, 'anak' => false] as $username => $isAdmin) {
            $this->sweepUser($this->loginHeaders($username), $isAdmin);
        }
    }

    public function test_new_registered_account_has_no_errors_and_full_flow_works(): void
    {
        $reg = $this->postJson('/api/register', [
            'username' => 'Pengguna_Baru', 'name' => 'Pengguna Baru', 'password' => 'rahasia1',
            'birth_date' => now()->subYears(20)->toDateString(),
        ])->assertStatus(201);

        $h = ['Authorization' => 'Bearer ' . $reg->json('token')];
        $this->assertSame('pengguna_baru', $reg->json('user.username'));
        $this->sweepUser($h);

        // Alur lengkap akun baru: keranjang -> checkout -> tonton -> riwayat
        $movie = Movie::where('age_limit', '<=', 13)->first();
        $movie->update(['price' => 12000]);
        $this->withHeaders($h)->postJson("/api/cart/{$movie->id}")->assertStatus(201);
        $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'QRIS'])->assertStatus(201);
        $this->withHeaders($h)->postJson("/api/movies/{$movie->id}/watch")->assertOk();
        $this->assertCount(1, $this->withHeaders($h)->getJson('/api/me/watch-history')->json('data'));

        // Bisa login lagi dengan username huruf besar maupun kecil
        $this->postJson('/api/login', ['username' => 'PENGGUNA_BARU', 'password' => 'rahasia1'])->assertOk();
    }

    public function test_age_rules_for_default_child_and_teen_accounts(): void
    {
        $adult = Movie::where('age_limit', '>=', 18)->first();
        $adult->update(['price' => 5000]);
        $teen = Movie::where('age_limit', 13)->first();
        $teen->update(['price' => 5000]);

        $anak = $this->loginHeaders('anak');
        $this->withHeaders($anak)->getJson("/api/movies/{$adult->id}")->assertStatus(403);
        $this->withHeaders($anak)->getJson("/api/movies/{$teen->id}")->assertStatus(403);
        $this->withHeaders($anak)->postJson("/api/cart/{$teen->id}")->assertStatus(403);
        $this->withHeaders($anak)->postJson("/api/movies/{$adult->id}/purchase", ['payment_method' => 'QRIS'])->assertStatus(403);

        $remaja = $this->loginHeaders('remaja');
        $this->withHeaders($remaja)->getJson("/api/movies/{$adult->id}")->assertStatus(403);
        $this->withHeaders($remaja)->getJson("/api/movies/{$teen->id}")->assertOk();
    }

    public function test_disabled_account_cannot_login_and_default_admin_cannot_lock_itself(): void
    {
        $admin = $this->loginHeaders('admin');
        $anak = User::where('username', 'anak')->first();

        $this->withHeaders($admin)->putJson("/api/admin/users/{$anak->id}", ['is_active' => false])->assertOk();
        $this->postJson('/api/login', ['username' => 'anak', 'password' => 'password'])->assertStatus(422);

        $self = User::where('username', 'admin')->first();
        $this->withHeaders($admin)->putJson("/api/admin/users/{$self->id}", ['role' => 'user'])->assertStatus(422);
    }
}
