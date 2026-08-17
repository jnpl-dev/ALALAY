<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AssistanceCode;
use App\Models\AssistanceCodeReference;
use App\Models\AuditLog;
use App\Models\RequiredDocument;
use App\Models\SocialCaseStudy;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Policies\ApplicationPolicy;
use App\Policies\AssistanceCategoryPolicy;
use App\Policies\AssistanceCodePolicy;
use App\Policies\AssistanceCodeReferencePolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\RequiredDocumentPolicy;
use App\Policies\SocialCaseStudyPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\VoucherPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Application::class => ApplicationPolicy::class,
        AssistanceCategory::class => AssistanceCategoryPolicy::class,
        AssistanceCode::class => AssistanceCodePolicy::class,
        AssistanceCodeReference::class => AssistanceCodeReferencePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        RequiredDocument::class => RequiredDocumentPolicy::class,
        SocialCaseStudy::class => SocialCaseStudyPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        User::class => UserPolicy::class,
        Voucher::class => VoucherPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $testRecipient = config('mail.test_recipient');

        if ($testRecipient) {
            Event::listen(MessageSending::class, function (MessageSending $event) use ($testRecipient) {
                $event->message->to($testRecipient);
            });
        }

        if (app()->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time > 200) {
                    Log::warning('Slow query detected', [
                        'sql'      => $query->sql,
                        'bindings' => $query->bindings,
                        'time'     => $query->time . 'ms',
                    ]);
                }
            });
        }

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinutes(15, 5)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Contact form limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('application_submit', function (Request $request) {
            $phone = preg_replace('/\D/', '', (string) $request->input('claimant_phone', ''));
            $ip = $request->ip();

            return [
                Limit::perDay(3)
                    ->by($phone)
                    ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Application submission limit reached for phone: ' . $phone)),
                Limit::perDay(10)
                    ->by($ip)
                    ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Application submission limit reached for IP: ' . $ip)),
            ];
        });

        RateLimiter::for('resubmit', function (Request $request) {
            return Limit::perMinutes(30, 5)
                ->by($request->route('referenceCode') . '|' . $request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Document resubmission limit reached for reference: ' . $r->route('referenceCode')));
        });

        RateLimiter::for('track_poll', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Track polling limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('track_show', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Track page limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('otp_verify', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'OTP verification limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('otp_resend', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'OTP resend limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('forgot_password', function (Request $request) {
            return Limit::perMinutes(5, 2)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Password reset request limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('reset_password', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Password reset submission limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('public_validate', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Validation endpoint limit reached for IP: ' . $r->ip()));
        });

        RateLimiter::for('assisted_submit', function (Request $request) {
            return Limit::perDay(30)
                ->by((string) ($request->user()?->id ?? $request->ip()))
                ->response(fn (Request $r) => $this->rateLimitResponse($r, 'Assisted application limit reached for user: ' . ($r->user()?->id ?? $r->ip())));
        });
    }

    protected function rateLimitResponse(Request $request, string $description): \Symfony\Component\HttpFoundation\Response
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role,
            'module' => 'security',
            'action' => 'rate_limited',
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('error', 'Too many requests. Please try again later.');
        }

        return response()->json([
            'message' => 'Too many requests. Please try again later.',
        ], 429);
    }
}
