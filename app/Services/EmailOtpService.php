<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public function generate(User $user): EmailOtp
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $otp = EmailOtp::create([
            'user_id' => $user->id,
            'otp_code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new SendOtpMail($code, $user));

        return $otp;
    }

    public function verify(User $user, string $code): bool
    {
        $otps = EmailOtp::where('user_id', $user->id)
            ->pending()
            ->latest()
            ->get();

        if ($otps->isEmpty()) {
            return false;
        }

        $matched = $otps->first(fn ($otp) => $otp->attempts < 5
            && Hash::check($code, $otp->otp_code));

        if (! $matched) {
            $otps->first()->increment('attempts');
            return false;
        }

        $matched->update([
            'used_at' => now(),
            'attempts' => $matched->attempts + 1,
        ]);

        EmailOtp::where('user_id', $user->id)
            ->where('id', '!=', $matched->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        return true;
    }
}
