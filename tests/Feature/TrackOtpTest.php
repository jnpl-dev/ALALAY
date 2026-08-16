<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackOtpTest extends TestCase
{
    use RefreshDatabase;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('sms.driver', 'log');

        $category = AssistanceCategory::create([
            'id' => (string) Str::uuid(),
            'category_name' => 'Test Category',
            'is_active' => true,
        ]);

        $this->application = Application::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'reference_code' => 'TEST-OTP-001',
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

    public function test_track_page_requires_otp_when_not_verified(): void
    {
        $response = $this->get(route('track.show', 'TEST-OTP-001'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Track')
            ->where('otp_required', true)
            ->where('otp_sent', false)
            ->where('application', null)
        );
    }

    public function test_send_otp_stores_otp_in_session_and_redirects(): void
    {
        $response = $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response->assertRedirect(route('track.show', 'TEST-OTP-001'));
        $response->assertSessionHas('track_otp_TEST-OTP-001');

        $stored = session('track_otp_TEST-OTP-001');
        $this->assertArrayHasKey('code', $stored);
        $this->assertArrayHasKey('expires_at', $stored);
        $this->assertEquals(0, $stored['attempts']);
    }

    public function test_otp_sent_flag_appears_after_sending(): void
    {
        $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response = $this->get(route('track.show', 'TEST-OTP-001'));
        $response->assertInertia(fn ($page) => $page
            ->where('otp_sent', true)
            ->where('otp_expired', false)
        );
    }

    public function test_verify_with_correct_otp_grants_access(): void
    {
        $code = '123456';
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $response = $this->post(route('track.verify-otp', 'TEST-OTP-001'), [
            'otp_code' => $code,
        ]);

        $response->assertRedirect(route('track.show', 'TEST-OTP-001'));
        $this->assertTrue(session('track_verified_TEST-OTP-001'));
    }

    public function test_track_page_shows_application_after_otp_verified(): void
    {
        $code = '123456';
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);
        $this->post(route('track.verify-otp', 'TEST-OTP-001'), ['otp_code' => $code]);

        $response = $this->get(route('track.show', 'TEST-OTP-001'));

        $response->assertInertia(fn ($page) => $page
            ->where('otp_required', false)
            ->has('application')
            ->where('application.reference_code', 'TEST-OTP-001')
        );
    }

    public function test_verify_with_wrong_otp_increments_attempts(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $response = $this->post(route('track.verify-otp', 'TEST-OTP-001'), [
            'otp_code' => '000000',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertEquals(1, session('track_otp_TEST-OTP-001')['attempts']);
    }

    public function test_verify_with_wrong_otp_does_not_grant_access(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);
        $this->post(route('track.verify-otp', 'TEST-OTP-001'), ['otp_code' => '000000']);

        $response = $this->get(route('track.show', 'TEST-OTP-001'));

        $response->assertInertia(fn ($page) => $page
            ->where('otp_required', true)
        );
    }

    public function test_verify_with_expired_otp_returns_error(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
            'attempts' => 0,
        ]);

        $response = $this->post(route('track.verify-otp', 'TEST-OTP-001'), [
            'otp_code' => '123456',
        ]);

        $response->assertSessionHasErrors('otp_code');
    }

    public function test_verify_with_too_many_attempts_is_blocked(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 5,
        ]);

        $response = $this->post(route('track.verify-otp', 'TEST-OTP-001'), [
            'otp_code' => '123456',
        ]);

        $response->assertSessionHasErrors('otp_code');
    }

    public function test_send_otp_returns_not_found_for_nonexistent_reference(): void
    {
        $response = $this->post(route('track.send-otp', 'NONEXISTENT'));
        $response->assertNotFound();
    }

    public function test_verify_otp_requires_otp_code_field(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $response = $this->post(route('track.verify-otp', 'TEST-OTP-001'), []);
        $response->assertSessionHasErrors('otp_code');
    }

    public function test_resend_during_cooldown_is_blocked(): void
    {
        $this->post(route('track.send-otp', 'TEST-OTP-001'));
        $stored = session('track_otp_TEST-OTP-001');
        $this->assertNotNull($stored);

        $response = $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response->assertSessionHasErrors('otp_code');
    }

    public function test_resend_after_cooldown_succeeds(): void
    {
        $this->post(route('track.send-otp', 'TEST-OTP-001'));
        session()->put('track_resend_TEST-OTP-001', [
            'count' => 1,
            'available_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response->assertRedirect(route('track.show', 'TEST-OTP-001'));
        $this->assertEquals(0, session('track_otp_TEST-OTP-001')['attempts']);
        $this->assertEquals(2, session('track_resend_TEST-OTP-001')['count']);
    }

    public function test_resend_limit_of_three_is_enforced(): void
    {
        session()->put('track_otp_TEST-OTP-001', [
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);
        session()->put('track_resend_TEST-OTP-001', [
            'count' => 3,
            'available_at' => now()->subMinute(),
        ]);

        $response = $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response->assertSessionHasErrors('otp_code');
    }

    public function test_flags_passed_to_track_page(): void
    {
        $this->post(route('track.send-otp', 'TEST-OTP-001'));

        $response = $this->get(route('track.show', 'TEST-OTP-001'));

        $response->assertInertia(fn ($page) => $page
            ->where('otp_resend_count', 0)
            ->where('otp_resend_limit', 3)
            ->where('otp_resend_available_at', session('track_resend_TEST-OTP-001')['available_at']->toISOString())
        );
    }
}
