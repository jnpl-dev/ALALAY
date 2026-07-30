<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SmsService $service;
    private string $applicationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SmsService::class);
        Config::set('sms.driver', 'log');

        $category = AssistanceCategory::create([
            'id' => (string) Str::uuid(),
            'category_name' => 'Test Category',
            'is_active' => true,
        ]);

        $application = Application::create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'reference_code' => 'SMS-TEST-001',
            'status' => 'submitted',
            'submission_type' => 'online',
            'claimant_last_name' => 'Test',
            'claimant_first_name' => 'User',
            'claimant_sex' => 'male',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => '123 Test St',
            'claimant_phone' => '09123456789',
            'claimant_relationship_to_beneficiary' => 'Self',
            'beneficiary_last_name' => 'Test',
            'beneficiary_first_name' => 'Ben',
            'beneficiary_sex' => 'female',
            'beneficiary_dob' => '1995-05-05',
            'beneficiary_address' => '456 Test Ave',
        ]);

        $this->applicationId = $application->id;
    }

    public function test_formats_philippine_phone_numbers(): void
    {
        $ref = new \ReflectionMethod($this->service, 'formatPhoneNumber');

        $this->assertEquals('639123456789', $ref->invoke($this->service, '09123456789'));
        $this->assertEquals('639123456789', $ref->invoke($this->service, '9123456789'));
        $this->assertEquals('639123456789', $ref->invoke($this->service, '639123456789'));
        $this->assertEquals('639123456789', $ref->invoke($this->service, '+639123456789'));
        $this->assertEquals('639123456789', $ref->invoke($this->service, '+63 912 345 6789'));
    }

    public function test_send_creates_notification_and_logs_when_driver_is_log(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($msg, $ctx) =>
                $msg === 'SmsService: SMS logged (driver=log)'
                && $ctx['recipient'] === '639123456789'
                && str_contains($ctx['message'], 'Your OTP is')
            );

        $notification = $this->service->send(
            '09123456789',
            'Your OTP is 123456',
            $this->applicationId,
            'test_event',
        );

        $this->assertEquals('639123456789', $notification->recipient_phone);
        $this->assertEquals('sent', $notification->status);
        $this->assertEquals('test_event', $notification->trigger_event);
    }

    public function test_send_marks_failed_on_missing_token(): void
    {
        Config::set('sms.driver', 'philsms');
        Config::set('sms.philsms.api_token', '');

        $notification = $this->service->send(
            '09123456789',
            'Test message',
            $this->applicationId,
            'test_fail',
        );

        $this->assertEquals('failed', $notification->status);
        $this->assertEquals(
            'PHILSMS_API_TOKEN is not configured',
            $notification->provider_response['error'],
        );
    }
}
