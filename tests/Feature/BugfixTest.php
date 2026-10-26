<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Satu tes per bug yang ditemukan saat audit (lihat CHANGELOG 2.2.1). */
class BugfixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $role = 'user', int $age = 25): array
    {
        app('auth')->forgetGuards();
        $user = User::factory()->create(['role' => $role, 'birth_date' => Carbon::now()->subYears($age)->toDateString()]);

        return [$user, ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken]];
    }

    private function paid(int $price = 10000): Movie
    {
        $m = Movie::where('age_limit', '<=', 13)->first();
        $m->update(['price' => $price, 'is_active' => true]);

        return $m;
    }

    // 1. Daftar
    public function test_register_rejects_duplicate_email_and_case_variants_of_username(): void
    {
        $base = ['name' => 'X', 'password' => 'rahasia1', 'birth_date' => '2000-01-01'];

        $this->postJson('/api/register', $base + ['username' => 'baru1', 'email' => 'sama@example.com'])->assertStatus(201);
        $this->postJson('/api/register', $base + ['username' => 'baru2', 'email' => 'sama@example.com'])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/api/register', $base + ['username' => 'DEWASA'])->assertStatus(422)->assertJsonValidationErrors('username');
        $this->postJson('/api/register', $base + ['username' => 'Admin'])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    // 2. Genre
    public function test_duplicate_genre_name_returns_422_not_500(): void
    {
        [, $h] = $this->as('admin');
        $existing = Genre::first();

        $this->withHeaders($h)->postJson('/api/admin/genres', ['name' => $existing->name])->assertStatus(422)->assertJsonValidationErrors('slug');
        $this->withHeaders($h)->postJson('/api/admin/genres', ['name' => 'Genre Uji Baru'])->assertStatus(201);

        $other = Genre::where('id', '!=', $existing->id)->first();
        $this->withHeaders($h)->putJson("/api/admin/genres/{$other->id}", ['name' => $existing->name])->assertStatus(422);
        $this->withHeaders($h)->putJson("/api/admin/genres/{$other->id}", ['name' => $other->name])->assertOk(); // nama sendiri tetap boleh
    }

    // 3. Login & logout
    public function test_login_is_case_insensitive_and_logout_only_revokes_current_token(): void
    {
        $this->postJson('/api/login', ['username' => 'DeWasA', 'password' => 'password'])->assertOk();

        [$user, $h1] = $this->as();
        $h2 = ['Authorization' => 'Bearer ' . $user->createToken('perangkat-2')->plainTextToken];

        $this->withHeaders($h1)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 2); // token login awal + perangkat-2; token h1 sudah dicabut

        app('auth')->forgetGuards();
        $this->withHeaders($h2)->getJson('/api/me')->assertOk(); // perangkat lain tetap login
    }

    // 4. Film nonaktif, tonton, pembayaran, keranjang
    public function test_inactive_movie_cannot_be_viewed_watched_or_bought(): void
    {
        [, $h] = $this->as();
        $m = $this->paid();
        $m->update(['is_active' => false]);

        $this->withHeaders($h)->getJson("/api/movies/{$m->id}")->assertStatus(404);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/purchase", ['payment_method' => 'QRIS'])->assertStatus(404);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/watch")->assertStatus(404);
    }

    public function test_watch_requires_ownership_for_paid_movies_only(): void
    {
        [, $h] = $this->as();
        $m = $this->paid();

        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/watch")->assertStatus(403);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/purchase", ['payment_method' => 'QRIS'])->assertStatus(201);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/watch")->assertOk();

        $free = Movie::where('age_limit', 0)->where('id', '!=', $m->id)->first();
        $free->update(['price' => 0]);
        $this->withHeaders($h)->postJson("/api/movies/{$free->id}/watch")->assertOk();
    }

    public function test_purchase_validates_method_and_clears_cart_item(): void
    {
        [$user, $h] = $this->as();
        $m = $this->paid();

        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/purchase", ['payment_method' => 'Koin Emas'])->assertStatus(422);

        $this->withHeaders($h)->postJson("/api/cart/{$m->id}")->assertStatus(201);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/purchase", ['payment_method' => 'QRIS'])->assertStatus(201);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id, 'movie_id' => $m->id]);
    }

    public function test_per_page_is_capped(): void
    {
        [, $h] = $this->as();
        $this->assertLessThanOrEqual(100, $this->withHeaders($h)->getJson('/api/movies?per_page=100000')->json('per_page'));
    }

    // 5. Keranjang
    public function test_cart_ignores_inactive_movies_and_rejects_empty_checkout(): void
    {
        [$user, $h] = $this->as();
        $m = $this->paid();
        $this->withHeaders($h)->postJson("/api/cart/{$m->id}")->assertStatus(201);

        $m->update(['is_active' => false]);
        $this->withHeaders($h)->getJson('/api/cart')->assertOk()->assertJsonPath('count', 0)->assertJsonPath('total', 0);
        $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'QRIS'])->assertStatus(422);
        $this->assertSame(0, CartItem::where('user_id', $user->id)->count()); // item usang dibersihkan
    }

    public function test_checkout_with_only_owned_movies_returns_422(): void
    {
        [$user, $h] = $this->as();
        $m = $this->paid();
        $this->withHeaders($h)->postJson("/api/cart/{$m->id}")->assertStatus(201);
        $this->withHeaders($h)->postJson("/api/movies/{$m->id}/purchase", ['payment_method' => 'QRIS'])->assertStatus(201);
        CartItem::create(['user_id' => $user->id, 'movie_id' => $m->id]); // sisa item lama

        $this->withHeaders($h)->postJson('/api/cart/checkout', ['payment_method' => 'QRIS'])->assertStatus(422);
    }

    // 6. Admin: kosongkan genre film
    public function test_admin_can_clear_all_genres_of_a_movie(): void
    {
        [, $h] = $this->as('admin');
        $m = Movie::has('genres')->first();
        $this->assertGreaterThan(0, $m->genres()->count());

        $this->withHeaders($h)->putJson("/api/admin/movies/{$m->id}", [
            'title' => $m->title, 'year' => $m->year, 'rating' => $m->rating, 'age_limit' => $m->age_limit, 'genres_sent' => 1,
        ])->assertOk();

        $this->assertSame(0, $m->genres()->count());
    }

    // 10. Waktu struk: tersimpan UTC, tampil WIB
    public function test_receipt_time_is_shown_in_display_timezone(): void
    {
        config(['app.timezone' => 'UTC']);
        [$user, $h] = $this->as();
        $m = $this->paid();
        $t = \App\Models\Transaction::create([
            'order_number' => 'TRX-UJI-1', 'user_id' => $user->id, 'movie_id' => $m->id, 'amount' => 1,
            'payment_method' => 'QRIS', 'status' => 'success', 'paid_at' => '2026-10-26 14:16:00',
        ]);

        $this->assertSame('26 Okt 2026, 21:16 WIB', $t->fresh()->formatted_paid_at);
    }
}
