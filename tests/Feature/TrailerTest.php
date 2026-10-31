<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrailerTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $role): array
    {
        $user = User::factory()->create(['role' => $role, 'birth_date' => Carbon::now()->subYears(25)->toDateString()]);

        return ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken];
    }

    private function payload(array $extra = []): array
    {
        return ['title' => 'Film Trailer', 'year' => 2024, 'rating' => 8, 'price' => 0, 'age_limit' => 0] + $extra;
    }

    public function test_embed_url_conversion(): void
    {
        $yt = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';
        $this->assertSame($yt, Movie::embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=5'));
        $this->assertSame($yt, Movie::embedUrl('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('https://player.vimeo.com/video/12345', Movie::embedUrl('https://vimeo.com/12345'));
        $this->assertNull(Movie::embedUrl('https://evil.example/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Movie::embedUrl('javascript:alert(1)'));
        $this->assertNull(Movie::embedUrl(''));
    }

    public function test_admin_saves_trailer_and_user_sees_embed(): void
    {
        $ah = $this->token('admin');
        $id = $this->withHeaders($ah)->postJson('/api/admin/movies', $this->payload(['trailer_url' => 'https://youtu.be/dQw4w9WgXcQ']))
            ->assertStatus(201)->json('data.id');

        $this->app['auth']->forgetGuards();
        $this->withHeaders($this->token('user'))->getJson("/api/movies/{$id}")
            ->assertOk()->assertJsonPath('data.trailer_embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    }

    public function test_invalid_trailer_rejected_and_can_be_cleared(): void
    {
        $ah = $this->token('admin');
        $this->withHeaders($ah)->postJson('/api/admin/movies', $this->payload(['trailer_url' => 'https://evil.example/x']))
            ->assertStatus(422)->assertJsonValidationErrors('trailer_url');

        $id = $this->withHeaders($ah)->postJson('/api/admin/movies', $this->payload(['trailer_url' => 'https://vimeo.com/999']))->json('data.id');
        $this->withHeaders($ah)->putJson("/api/admin/movies/{$id}", $this->payload(['trailer_url' => '']))->assertOk();
        $this->assertNull(Movie::find($id)->trailer_url);
    }
}
