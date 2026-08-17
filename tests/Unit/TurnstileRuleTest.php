<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TurnstileRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('turnstile.enabled', true);
        Config::set('turnstile.site_key', 'test-site-key');
        Config::set('turnstile.secret_key', 'test-secret-key');
    }

    private function assertRulePasses($value): void
    {
        $validator = Validator::make(
            ['cf-turnstile-response' => $value],
            ['cf-turnstile-response' => ['nullable', new Turnstile]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_disabled_when_no_secret_key_configured(): void
    {
        Config::set('turnstile.enabled', false);
        Config::set('turnstile.secret_key', null);

        $this->assertFalse(config('turnstile.enabled'));
        $this->assertRulePasses('');
    }

    public function test_missing_token_is_bypassed_and_logged(): void
    {
        $this->assertRulePasses('');

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'turnstile_bypass',
        ]);
    }

    public function test_successful_siteverify_passes(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ]),
        ]);

        $this->assertRulePasses('valid-token');
    }

    public function test_failed_siteverify_fails_validation(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'invalid-token'],
            ['cf-turnstile-response' => ['nullable', new Turnstile]]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('Please complete the security check.', $validator->errors()->first('cf-turnstile-response'));
    }

    public function test_unreachable_siteverify_is_bypassed_and_logged(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(null, 503),
        ]);

        $this->assertRulePasses('token-while-down');

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'security',
            'action' => 'turnstile_bypass',
        ]);
    }

    public function test_malformed_siteverify_response_fails_validation(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response('not-json', 200),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'token-while-malformed'],
            ['cf-turnstile-response' => ['nullable', new Turnstile]]
        );

        $this->assertTrue($validator->fails());
    }
}