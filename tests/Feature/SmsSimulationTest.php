<?php

namespace Tests\Feature;

use App\Jobs\SendSmsJob;
use App\Mail\SendApplicationOtpMail;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\SmsNotification;
use App\Models\SystemSetting;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmsSimulationTest extends TestCase
{
    use RefreshDatabase;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('sms.driver', 'log');
        Config::set('queue.default', 'database');

        Log::spy();

        SystemSetting::create([
            'id' => (string) Str::uuid(),
            'setting_key' => 'sms_enabled',
            'setting_value' => 'true',
        ]);

        $category = AssistanceCategory::create([
            'id' => (string) Str::uuid(),
            'category_name' => 'Sim Cat',
            'is_active' => true,
        ]);

        $this->application = Application::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'reference_code' => 'SIM-2026-0001',
            'status' => 'submitted',
            'submission_type' => 'online',
            'claimant_last_name' => 'Doe',
            'claimant_first_name' => 'Jane',
            'claimant_sex' => 'female',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => '123 Test St',
            'claimant_phone' => '09171234567',
            'claimant_email' => 'applicant@example.com',
            'claimant_relationship_to_beneficiary' => 'Self',
            'beneficiary_last_name' => 'Doe',
            'beneficiary_first_name' => 'Jane',
            'beneficiary_sex' => 'female',
            'beneficiary_dob' => '1990-01-01',
            'beneficiary_address' => '123 Test St',
        ]);
    }

    public function test_simulate_all_five_sms_events(): void
    {
        $this->application->update(['status' => 'mswdo_review', 'resubmission_remarks' => 'Missing birth certificate']);
        $this->application->update(['status' => 'cheque_ready']);
        $this->application->update(['claiming_date' => '2026-09-01']);

        $events = ['submission_complete', 'application_under_review', 'resubmission_needed', 'cheque_ready', 'cheque_claiming'];

        foreach ($events as $event) {
            (new SendSmsJob($this->application, $event))->handle(app(SmsService::class));
        }

        $this->assertDatabaseCount('sms_notifications', 5);
        $this->assertSame(5, SmsNotification::where('status', 'sent')->count());

        $claiming = SmsNotification::where('trigger_event', 'cheque_claiming')->first();
        $this->assertStringContainsString('September 1, 2026', $claiming->message_body);
        $this->assertStringContainsString('SIM-2026-0001', $claiming->message_body);

        $resub = SmsNotification::where('trigger_event', 'resubmission_needed')->first();
        $this->assertStringContainsString('Missing birth certificate', $resub->message_body);
    }

    public function test_simulate_empty_template_falls_back_to_default(): void
    {
        SystemSetting::create([
            'id' => (string) Str::uuid(),
            'setting_key' => 'sms_template_cheque_ready',
            'setting_value' => '',
        ]);

        (new SendSmsJob($this->application, 'cheque_ready'))->handle(app(SmsService::class));

        $notification = SmsNotification::where('trigger_event', 'cheque_ready')->first();
        $this->assertEquals('sent', $notification->status);
        $this->assertStringContainsString('Your AICS cheque is ready for claiming', $notification->message_body);
    }

    public function test_simulate_sms_disabled_gate_blocks_sending(): void
    {
        SystemSetting::where('setting_key', 'sms_enabled')->update(['setting_value' => 'false']);
        Cache::forget('settings.sms_enabled');

        $notification = app(SmsService::class)->send('09171234567', 'Test', $this->application->id, 'track_otp');

        $this->assertEquals('failed', $notification->status);
        $this->assertEquals('SMS disabled by sms_enabled setting', $notification->provider_response['error']);
    }

    public function test_simulate_otp_endpoint_falls_back_to_email_when_sms_fails(): void
    {
        Mail::fake();
        SystemSetting::where('setting_key', 'sms_enabled')->update(['setting_value' => 'false']);
        Cache::forget('settings.sms_enabled');

        $this->post(route('track.send-otp', 'SIM-2026-0001'), [], ['Accept' => 'text/html'])
            ->assertRedirect(route('track.show', 'SIM-2026-0001'));

        $this->assertDatabaseHas('sms_notifications', [
            'trigger_event' => 'track_otp',
            'status' => 'failed',
        ]);

        Mail::assertQueued(SendApplicationOtpMail::class);
    }

    public function test_simulate_bulk_claiming_queues_job_and_sets_date(): void
    {
        $this->application->update(['status' => 'cheque_ready']);

        $applications = Application::where('status', 'cheque_ready')->get();

        foreach ($applications as $app) {
            $app->update(['claiming_date' => '2026-09-01']);
            SendSmsJob::dispatch($app, 'cheque_claiming');
        }

        $this->assertSame('2026-09-01', $this->application->fresh()->claiming_date?->format('Y-m-d'));
        $this->assertDatabaseCount('jobs', 1);

        $this->assertDatabaseCount('sms_notifications', 0);

        app('queue')->pop()->fire();

        $this->assertDatabaseCount('sms_notifications', 1);
        $notification = SmsNotification::first();
        $this->assertEquals('cheque_claiming', $notification->trigger_event);
        $this->assertEquals('sent', $notification->status);
        $this->assertStringContainsString('September 1, 2026', $notification->message_body);
    }
}