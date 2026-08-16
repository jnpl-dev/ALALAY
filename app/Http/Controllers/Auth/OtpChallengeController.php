<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpChallengeRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OtpChallengeController extends Controller
{
    public function show()
    {
        if (! session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/EmailOtpChallenge', [
            'otp_available_at' => session('otp_resend_available_at'),
            'otp_resend_count' => session('otp_resend_count', 0),
            'otp_resend_limit' => 3,
            'otp_cooldown_seconds' => 60,
        ]);
    }

    public function verify(OtpChallengeRequest $request, EmailOtpService $otpService)
    {
        if (! session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $validated = $request->validated();

        $user = User::find(session('otp_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $otpService->verify($user, $validated['otp_code'])) {
            throw ValidationException::withMessages([
                'otp_code' => ['The verification code is invalid.'],
            ]);
        }

        $remember = session()->pull('otp_remember', false);
        session()->forget('otp_user_id');

        Auth::login($user, $remember);

        $user->update(['is_online' => true]);

        AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'module' => 'auth',
            'action' => 'login',
            'description' => sprintf('%s logged in', $user->full_name),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $request->session()->regenerate();

        $knownIps = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('ip_address', '!=', $request->ip())
            ->pluck('ip_address')
            ->toArray();

        if (empty($knownIps)) {
            $user->notify(new NewLoginDetected(
                ip: $request->ip(),
                userAgent: $request->userAgent(),
                loginAt: now(),
            ));
        }

        if ($user->acceptable_use_policy_accepted_at === null) {
            return redirect()->route('aup.show');
        }

        return redirect()->route('dashboard');
    }

    public function resend(Request $request, EmailOtpService $otpService)
    {
        if (! session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('otp_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        $resendCount = (int) session('otp_resend_count', 0);
        $availableAt = session('otp_resend_available_at')
            ? \Illuminate\Support\Carbon::parse(session('otp_resend_available_at'))
            : null;

        if ($resendCount >= 3) {
            throw ValidationException::withMessages([
                'otp_code' => ['Resend limit reached. Please try again later.'],
            ]);
        }

        if ($availableAt && now()->lt($availableAt)) {
            $remaining = max(1, (int) ceil(now()->diffInSeconds($availableAt)));
            throw ValidationException::withMessages([
                'otp_code' => ["Please wait {$remaining}s before requesting a new code."],
            ]);
        }

        $otpService->generate($user);

        session()->put('otp_resend_count', $resendCount + 1);
        session()->put('otp_resend_available_at', now()->addMinute());

        return redirect()->route('otp.challenge')->with('success', 'A new verification code has been sent to your email.');
    }
}
