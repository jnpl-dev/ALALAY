<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        protected Application $application,
        protected string $triggerEvent,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $template = $this->getTemplate();
        $message = $this->buildMessage($template);

        $smsService->send(
            recipient: $this->application->claimant_phone,
            message: $message,
            referenceCode: $this->application->reference_code,
        );
    }

    public function getTemplate(): string
    {
        $templates = [
            'submission_complete' => 'sms_template_submission_complete',
            'application_under_review' => 'sms_template_under_review',
            'resubmission_needed' => 'sms_template_resubmission_needed',
            'cheque_claiming' => 'sms_template_cheque_claiming',
        ];

        $key = $templates[$this->triggerEvent] ?? 'sms_template_submission_complete';

        return Cache::remember("settings.{$key}", 1800, fn () =>
            \App\Models\SystemSetting::byKey($key)->first()?->setting_value
        ) ?? $this->getDefaultTemplate();
    }

    public function buildMessage(string $template): string
    {
        $replacements = [
            '{reference_code}' => $this->application->reference_code,
            '{claimant_name}' => $this->application->claimant_first_name,
            '{track_url}' => route('track') . '?ref=' . $this->application->reference_code,
            '{remarks}' => $this->application->resubmission_remarks ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function getDefaultTemplate(): string
    {
        return match ($this->triggerEvent) {
            'submission_complete' => 'Your AICS application {reference_code} has been submitted. Track it at {track_url}.',
            'application_under_review' => 'Your application {reference_code} is now under review.',
            'resubmission_needed' => 'Your application {reference_code} requires resubmission. Reason: {remarks}. Track at {track_url}.',
            'cheque_claiming' => 'Your AICS cheque is ready for claiming. Please visit the MSWDO office. Ref: {reference_code}.',
            default => 'Your AICS application {reference_code} has been updated.',
        };
    }
}
