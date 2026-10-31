<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['username' => 'tidak_ada', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson('/api/login', ['username' => 'tidak_ada', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_array_query_params_do_not_cause_server_error(): void
    {
        $user = User::factory()->create(['birth_date' => Carbon::now()->subYears(25)->toDateString()]);
        $admin = User::factory()->create(['role' => 'admin', 'birth_date' => Carbon::now()->subYears(25)->toDateString()]);

        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/movies/search?q[]=x')->assertOk();
        $this->getJson('/api/movies?genre[]=x')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($admin, 'sanctum');
        $this->getJson('/api/admin/movies?q[]=x')->assertOk();
        $this->getJson('/api/admin/transactions?q[]=x&method[]=y')->assertOk();
    }
}
