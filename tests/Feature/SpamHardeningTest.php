<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AuditLog;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpamHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('turnstile.enabled', false);
        Config::set('sms.driver', 'log');
    }

    private function makeCategory(): RequiredDocument
    {
        $category = AssistanceCategory::create([
            'category_name' => 'Medical Assistance',
            'category_description' => 'Test category',
            'is_active' => true,
        ]);

        return RequiredDocument::create([
            'category_id' => $category->id,
            'doc_name' => 'Medical Certificate',
            'doc_description' => 'Required',
            'is_mandatory' => true,
            'is_active' => true,
            'capture_type' => 'document',
            'scanner_size' => 'a4',
        ]);
    }

    private function applyPayload($category, $docId, string $beneficiaryLastName): array
    {
        return [
            'category_id' => $category->id,
            'claimant_last_name' => 'Dela Cruz',
            'claimant_first_name' => 'Juan',
            'claimant_middle_name' => null,
            'claimant_name_extension' => null,
            'claimant_sex' => 'Male',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => 'Poblacion, General Mamerto Natividad, Nueva Ecija',
            'claimant_phone' => '09171234567',
            'claimant_email' => null,
            'claimant_relationship_to_beneficiary' => 'Parent',
            'beneficiary_last_name' => $beneficiaryLastName,
            'beneficiary_first_name' => 'Maria',
            'beneficiary_middle_name' => null,
            'beneficiary_name_extension' => null,
            'beneficiary_sex' => 'Female',
            'beneficiary_dob' => '2015-05-05',
            'beneficiary_address' => 'Poblacion, General Mamerto Natividad, Nueva Ecija',
            'documents' => [
                UploadedFile::fake()->create('medical.pdf', 200, 'application/pdf'),
            ],
            'document_ids' => [$docId],
        ];
    }

    public function test_contact_form_rate_limited_by_ip(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.send'), [
                'name' => 'Test User',
                'email' => "user{$i}@example.com",
                'message' => 'This is a test message that is long enough.',
            ])->assertRedirect();
        }

        $this->post(route('contact.send'), [
            'name' => 'Spammer',
            'email' => 'spam@example.com',
            'message' => 'This message should be rate limited.',
        ])->assertStatus(429);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'rate_limited',
            'ip_address' => '203.0.113.5',
        ]);
    }

    public function test_application_submit_rate_limited_per_phone(): void
    {
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/apply', $this->applyPayload($category, $document->id, "Beneficiary{$i}"))
                ->assertRedirect(route('apply'));
        }

        $this->post('/apply', $this->applyPayload($category, $document->id, 'FourthBeneficiary'))
            ->assertStatus(429);
    }

    public function test_contact_honeypot_blocks_bot_without_sending_mail(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.send'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'This message comes from an automated bot.',
            'company_website' => 'http://spam.example.com',
        ]);

        $response->assertRedirect();
        Mail::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'honeypot_triggered',
        ]);
    }

    public function test_contact_honeypot_empty_sends_mail(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Real User',
            'email' => 'real@example.com',
            'message' => 'A genuine inquiry message.',
            'company_website' => '',
        ])->assertRedirect();

        Mail::assertSent(\App\Mail\SendContactMail::class);
    }

    public function test_assisted_submission_does_not_run_turnstile(): void
    {
        Config::set('turnstile.enabled', true);
        Config::set('turnstile.secret_key', 'test-secret-key');

        $user = User::factory()->aicsStaff()->create();
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $this->actingAs($user)->post(
            route('aics.applications.store-assisted'),
            $this->applyPayload($category, $document->id, 'AssistedBeneficiary'),
        )->assertRedirect(route('aics.applications.create'));

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'security',
            'action' => 'turnstile_bypass',
        ]);

        $this->assertDatabaseHas('applications', [
            'submission_type' => 'walk_in',
        ]);
    }

    public function test_assisted_submit_rate_limited_per_user(): void
    {
        $user = User::factory()->aicsStaff()->create();
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $limiter = RateLimiter::limiter('assisted_submit');
        $this->assertNotNull($limiter);

        $key = md5('assisted_submit' . $user->id);
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($key);
        }

        $this->actingAs($user)->post(
            route('aics.applications.store-assisted'),
            $this->applyPayload($category, $document->id, 'RateLimitedBeneficiary'),
        )->assertStatus(429);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'rate_limited',
        ]);
    }

    public function test_public_submission_runs_turnstile_and_bypass_logs_when_missing_token(): void
    {
        Config::set('turnstile.enabled', true);
        Config::set('turnstile.secret_key', 'test-secret-key');

        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $this->post('/apply', $this->applyPayload($category, $document->id, 'PublicBeneficiary'))
            ->assertRedirect(route('apply'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'turnstile_bypass',
        ]);
    }

    public function test_application_submit_phone_key_is_normalized(): void
    {
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20']);

        for ($i = 0; $i < 3; $i++) {
            $payload = $this->applyPayload($category, $document->id, "Normalized{$i}");
            $this->post('/apply', $payload)->assertRedirect(route('apply'));
        }

        $spacedPayload = $this->applyPayload($category, $document->id, 'SpacedVariant');
        $spacedPayload['claimant_phone'] = '0917 123 4567';

        $this->post('/apply', $spacedPayload)->assertStatus(429);
    }

    public function test_track_show_rate_limited(): void
    {
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $this->post('/apply', $this->applyPayload($category, $document->id, 'TrackShowBeneficiary'))
            ->assertRedirect(route('apply'));

        $application = Application::where('category_id', $category->id)->first();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30']);

        for ($i = 0; $i < 30; $i++) {
            $this->get(route('track.show', $application->reference_code))->assertStatus(200);
        }

        $this->get(route('track.show', $application->reference_code))->assertStatus(429);
    }

    public function test_otp_verify_rate_limited(): void
    {
        $this->post(route('otp.verify'), ['otp_code' => '123456'])->assertRedirect(route('login'));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.40']);

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('otp.verify'), ['otp_code' => '123456']);
        }

        $this->post(route('otp.verify'), ['otp_code' => '123456'])->assertStatus(429);
    }

    public function test_otp_resend_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50']);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('otp.resend'))->assertRedirect(route('login'));
        }

        $this->post(route('otp.resend'))->assertStatus(429);
    }

    public function test_forgot_password_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.60']);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertRedirect();
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertRedirect();

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertStatus(429);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'rate_limited',
            'ip_address' => '203.0.113.60',
        ]);
    }

    public function test_reset_password_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.70']);

        $payload = [
            'token' => 'invalid-token',
            'email' => 'unknown@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update'), $payload);
        }

        $this->post(route('password.update'), $payload)->assertStatus(429);
    }
}
