<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class OtpBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function enableBypass(string $code = '482913'): void
    {
        Config::set('otp.bypass_enabled', true);
        Config::set('otp.bypass_code', $code);
    }

    protected function disableBypass(): void
    {
        Config::set('otp.bypass_enabled', false);
        Config::set('otp.bypass_code', null);
    }

    private function beginLogin(User $user): void
    {
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect(route('otp.challenge'));
    }

    public function test_login_bypass_rejected_when_flag_off(): void
    {
        $this->disableBypass();
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        $this->beginLogin($user);

        $response = $this->post(route('otp.challenge'), ['otp_code' => '482913']);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_login_bypass_accepted_when_flag_on(): void
    {
        $this->enableBypass();
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        $this->beginLogin($user);

        $response = $this->post(route('otp.challenge'), ['otp_code' => '482913']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_bypass_code_still_rejected_when_wrong_even_with_flag_on(): void
    {
        $this->enableBypass('482913');
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        $this->beginLogin($user);

        $response = $this->post(route('otp.challenge'), ['otp_code' => '000000']);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_login_bypass_writes_audit_row(): void
    {
        $this->enableBypass();
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        $this->beginLogin($user);
        $this->post(route('otp.challenge'), ['otp_code' => '482913']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
        $this->assertStringContainsString(
            'OTP bypassed',
            (string) \App\Models\AuditLog::where('action', 'login')->latest('id')->value('description')
        );
    }

    private function makeApplication(): Application
    {
        $category = AssistanceCategory::create([
            'id' => (string) Str::uuid(),
            'category_name' => 'Test Category',
            'is_active' => true,
        ]);

        return Application::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'reference_code' => 'BYPASS-001',
            'status' => 'submitted',
            'submission_type' => 'online',
            'claimant_last_name' => 'Doe',
            'claimant_first_name' => 'John',
            'claimant_sex' => 'male',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => '123 Test St',
            'claimant_phone' => '09123456789',
            'claimant_email' => 'john@example.com',
            'claimant_relationship_to_beneficiary' => 'Self',
            'beneficiary_last_name' => 'Doe',
            'beneficiary_first_name' => 'Jane',
            'beneficiary_sex' => 'female',
            'beneficiary_dob' => '1995-05-05',
            'beneficiary_address' => '456 Test Ave',
        ]);
    }

    public function test_track_bypass_rejected_when_flag_off(): void
    {
        Config::set('sms.driver', 'log');
        $this->disableBypass();
        $this->makeApplication();

        $response = $this->post(route('track.verify-otp', 'BYPASS-001'), [
            'otp_code' => '482913',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertNull(session('track_verified_BYPASS-001'));
    }

    public function test_track_bypass_accepted_when_flag_on_even_without_sent_otp(): void
    {
        Config::set('sms.driver', 'log');
        $this->enableBypass();
        $this->makeApplication();

        $response = $this->post(route('track.verify-otp', 'BYPASS-001'), [
            'otp_code' => '482913',
        ]);

        $response->assertRedirect(route('track.show', 'BYPASS-001'));
        $this->assertTrue(session('track_verified_BYPASS-001'));
    }

    public function test_track_bypass_code_still_rejected_when_wrong_even_with_flag_on(): void
    {
        Config::set('sms.driver', 'log');
        $this->enableBypass('482913');
        $this->makeApplication();

        $response = $this->post(route('track.verify-otp', 'BYPASS-001'), [
            'otp_code' => '999999',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertNull(session('track_verified_BYPASS-001'));
    }

    public function test_bypass_flag_off_by_default_from_config(): void
    {
        $this->assertFalse((bool) config('otp.bypass_enabled'));
    }
}
