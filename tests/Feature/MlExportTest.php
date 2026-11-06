<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use App\Models\WatchHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_downloads_csv_of_thriller_viewers_with_favorite_genres(): void
    {
        $this->seed();
        $thriller = Genre::where('slug', 'thriller')->firstOrFail();
        $drama = Genre::where('slug', '!=', 'thriller')->first();
        $movie = Movie::first();
        $movie->genres()->sync([$thriller->id]);

        $viewer = User::factory()->create(['birth_date' => Carbon::now()->subYears(20)->toDateString()]);
        $viewer->favoriteGenres()->sync([$drama->id]);
        WatchHistory::create(['user_id' => $viewer->id, 'movie_id' => $movie->id, 'watched_at' => now()]);
        $nonViewer = User::factory()->create();

        $admin = User::factory()->create(['role' => 'admin']);
        $res = $this->withToken($admin->createToken('t')->plainTextToken)->get('/api/admin/export/ml')->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));

        $rows = array_map('str_getcsv', array_filter(explode("\n", $res->streamedContent())));
        $header = $rows[0];
        $byId = collect(array_slice($rows, 1))->keyBy(0);
        $this->assertTrue($byId->has((string) $viewer->id));
        $this->assertFalse($byId->has((string) $nonViewer->id));
        $row = array_combine($header, $byId[(string) $viewer->id]);
        $this->assertSame('1', $row['fav_' . $drama->slug]);
        $this->assertSame('0', $row['fav_thriller']);
        $this->assertSame('1', $row['watch_thriller']);
        $this->assertSame('20', $row['age']);
        $this->assertNotContains('email', $header);
    }

    public function test_non_admin_forbidden_and_unknown_genre_404(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $this->withToken($user->createToken('t')->plainTextToken)->getJson('/api/admin/export/ml')->assertForbidden();
        app('auth')->forgetGuards();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withToken($admin->createToken('t')->plainTextToken)->getJson('/api/admin/export/ml?genre=tidak-ada')->assertNotFound();
    }
}
