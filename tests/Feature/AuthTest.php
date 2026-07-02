<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_with_valid_credentials_redirects_to_otp(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect(route('otp.challenge'));
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_blocked_for_non_admin(): void
    {
        $user = User::factory()->create([
            'role' => 'aics_staff',
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_aup_redirect_when_not_accepted(): void
    {
        $user = User::factory()->create([
            'acceptable_use_policy_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('aup.show'));
    }

    public function test_aup_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/acceptable-use-policy');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/AcceptableUsePolicy')
        );
    }

    public function test_aup_acceptance_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'acceptable_use_policy_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/acceptable-use-policy');

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->acceptable_use_policy_accepted_at);
    }
}
