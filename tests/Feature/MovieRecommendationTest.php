<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use App\Models\WatchHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_user_12_years_old_cannot_access_movies_above_12()
    {
        // User 12 tahun
        $child = User::factory()->create([
            'birth_date' => Carbon::now()->subYears(12)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 0.0,
        ]);

        $token = $child->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        // 1. Index test: tidak boleh ada film dengan age_limit > 12
        $response = $this->withHeaders($headers)->getJson('/api/movies');
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertLessThanOrEqual(12, $item['age_limit']);
        }

        // 2. Search test
        $response = $this->withHeaders($headers)->getJson('/api/movies/search?q=Inception');
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data')); // Inception rating 13+, child tidak boleh melihat

        // 3. Detail test direct access ke film 13+
        $inception = Movie::where('title', 'Inception')->first();
        $response = $this->withHeaders($headers)->getJson("/api/movies/{$inception->id}");
        $response->assertStatus(403);

        // 4. Recommendations test
        $response = $this->withHeaders($headers)->getJson('/api/recommendations');
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertLessThanOrEqual(12, $item['age_limit']);
        }
    }

    public function test_user_13_years_old_can_see_13_plus_movies()
    {
        $teen = User::factory()->create([
            'birth_date' => Carbon::now()->subYears(13)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 0.0,
        ]);

        $token = $teen->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $inception = Movie::where('title', 'Inception')->first();
        $response = $this->withHeaders($headers)->getJson("/api/movies/{$inception->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Inception');
    }

    public function test_watched_movie_excluded_from_recommendations()
    {
        $adult = User::factory()->create([
            'birth_date' => Carbon::now()->subYears(25)->toDateString(),
            'role' => 'user',
            'minimum_rating' => 0.0,
        ]);

        $inception = Movie::where('title', 'Inception')->first();
        WatchHistory::create([
            'user_id' => $adult->id,
            'movie_id' => $inception->id,
            'watched_at' => now(),
        ]);

        $token = $adult->createToken('test')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/recommendations');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($inception->id, $ids);
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        $user = User::factory()->create([
            'birth_date' => Carbon::now()->subYears(20)->toDateString(),
            'role' => 'user',
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    public function test_birth_date_cannot_be_in_future()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'futureguy',
            'name' => 'Future Guy',
            'email' => 'future@example.com',
            'password' => 'secret123',
            'birth_date' => Carbon::now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('birth_date');
    }
}
