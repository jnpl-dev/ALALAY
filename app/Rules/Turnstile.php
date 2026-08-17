<?php

namespace App\Rules;

use App\Models\AuditLog;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('turnstile.enabled')) {
            return;
        }

        if (empty($value)) {
            $this->logBypass('Token was not provided.');
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Exception $e) {
            $this->logBypass('Verification service unreachable: ' . $e->getMessage());
            return;
        }

        if (! $response->successful()) {
            $this->logBypass('Verification service returned a non-success response.');
            return;
        }

        if ($response->json('success') !== true) {
            $fail('Please complete the security check.');
            return;
        }
    }

    private function logBypass(string $reason): void
    {
        AuditLog::create([
            'user_id' => null,
            'role' => null,
            'module' => 'security',
            'action' => 'turnstile_bypass',
            'description' => 'Turnstile bypassed. Reason: ' . $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}