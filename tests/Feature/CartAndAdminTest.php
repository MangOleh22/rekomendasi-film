<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CartAndAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function actingToken(string $role = 'user', int $age = 25): array
    {
        $user = User::factory()->create(['role' => $role, 'birth_date' => Carbon::now()->subYears($age)->toDateString()]);

        return [$user, ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken]];
    }

    private function paidMovies(int $n = 2)
    {
        return Movie::where('age_limit', '<=', 13)->take($n)->get()->each(fn ($m, $i) => $m->update(['price' => 10000 * ($i + 1)]));
    }

    public function test_cart_add_total_and_checkout_creates_one_invoice(): void
    {
        [$user, $h] = $this->actingToken();
        [$a, $b] = $this->paidMovies(2);

        $this->withHeaders($h)->postJson("/api/cart/{$a->id}")->assertStatus(201);
        $this->withHeaders($h)->postJson("/api/cart/{$a->id}")->assertStatus(201); // idempoten, tidak dobel
        $this->withHeaders($h)->postJson("/api/cart/{$b->id}")->assertStatus(201);

        $cart = $this->withHeaders($h)->getJson('/api/cart')->assertOk();
        $cart->assertJsonPath('count', 2)->assertJsonPath('total', 30000)->assertJsonPath('formatted_total', 'Rp 30.000');

        $res = $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'BCA Virtual Account'])->assertStatus(201);
        $res->assertJsonPath('count', 2)->assertJsonPath('total', 30000);
        $this->assertStringStartsWith('INV-', $res->json('invoice_number'));

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'movie_id' => $a->id, 'invoice_number' => $res->json('invoice_number')]);
        // film yang sudah dibeli tidak bisa dimasukkan lagi
        $this->withHeaders($h)->postJson("/api/cart/{$a->id}")->assertStatus(422);
    }

    public function test_cart_rejects_free_movies_empty_checkout_and_invalid_method(): void
    {
        [, $h] = $this->actingToken();
        $free = Movie::where('age_limit', '<=', 13)->first();
        $free->update(['price' => 0]);

        $this->withHeaders($h)->postJson("/api/cart/{$free->id}")->assertStatus(422);
        $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'QRIS'])->assertStatus(422);
        $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'Koin'])->assertStatus(422);
    }

    public function test_underage_user_cannot_add_adult_movie_to_cart(): void
    {
        [, $h] = $this->actingToken('user', 12);
        $adult = Movie::where('age_limit', '>=', 18)->first();
        $adult->update(['price' => 5000]);

        $this->withHeaders($h)->postJson("/api/cart/{$adult->id}")->assertStatus(403);
    }

    public function test_admin_sees_all_transactions_and_user_cannot(): void
    {
        [, $uh] = $this->actingToken();
        [, $ah] = $this->actingToken('admin');
        [$a] = $this->paidMovies(1);

        $this->withHeaders($uh)->postJson("/api/cart/{$a->id}");
        $this->withHeaders($uh)->postJson('/api/cart/checkout', ['payment_method' => 'QRIS'])->assertStatus(201);

        $this->withHeaders($uh)->getJson('/api/admin/transactions')->assertStatus(403);
        app('auth')->forgetGuards(); // guard Sanctum menyimpan pengguna request sebelumnya selama satu tes
        $list = $this->withHeaders($ah)->getJson('/api/admin/transactions')->assertOk();
        $list->assertJsonPath('data.0.movie.title', $a->title)->assertJsonPath('summary.revenue', 10000);
        $this->assertNotEmpty($list->json('data.0.user.username'));
    }

    public function test_admin_can_disable_enable_change_role_and_delete_user(): void
    {
        [$admin, $ah] = $this->actingToken('admin');
        [$target, $th] = $this->actingToken();

        // Disable: token dicabut & login ditolak
        $this->withHeaders($ah)->putJson("/api/admin/users/{$target->id}", ['is_active' => false])->assertOk();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertDatabaseCount('personal_access_tokens', 1); // hanya token admin
        $this->postJson('/api/login', ['username' => $target->username, 'password' => 'password'])->assertStatus(422);

        // Enable kembali, lalu ubah izin
        $this->withHeaders($ah)->putJson("/api/admin/users/{$target->id}", ['is_active' => true, 'role' => 'admin'])->assertOk();
        $this->assertSame('admin', $target->fresh()->role);

        // Tidak boleh mengunci / menghapus diri sendiri
        $this->withHeaders($ah)->putJson("/api/admin/users/{$admin->id}", ['role' => 'user'])->assertStatus(422);
        $this->withHeaders($ah)->putJson("/api/admin/users/{$admin->id}", ['is_active' => false])->assertStatus(422);
        $this->withHeaders($ah)->deleteJson("/api/admin/users/{$admin->id}")->assertStatus(422);

        $this->withHeaders($ah)->deleteJson("/api/admin/users/{$target->id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_poster_upload_has_no_size_limit(): void
    {
        [, $ah] = $this->actingToken('admin');

        $this->withHeaders($ah)->post('/api/admin/movies', [
            'title' => 'Film Poster Besar', 'year' => 2024, 'rating' => 8, 'price' => 0, 'age_limit' => 0,
            'poster' => UploadedFile::fake()->image('besar.jpg', 1200, 1800)->size(8192), // 8 MB (> batas lama 2 MB)
        ], ['Accept' => 'application/json'])->assertStatus(201);
    }
}
