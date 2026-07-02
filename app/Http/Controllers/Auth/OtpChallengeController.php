<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OtpChallengeController extends Controller
{
    public function show()
    {
        if (! session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/EmailOtpChallenge');
    }

    public function verify(Request $request, EmailOtpService $otpService)
    {
        if (! session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::find(session('otp_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $otpService->verify($user, $request->otp_code)) {
            throw ValidationException::withMessages([
                'otp_code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        $remember = session()->pull('otp_remember', false);
        session()->forget('otp_user_id');

        Auth::login($user, $remember);

        $user->update(['is_online' => true]);

        $request->session()->regenerate();

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

        $otpService->generate($user);

        return redirect()->route('otp.challenge')->with('success', 'A new verification code has been sent to your email.');
    }
}
