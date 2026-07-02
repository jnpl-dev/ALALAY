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
        $this->invalidatePrevious($user);

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
        $otp = EmailOtp::where('user_id', $user->id)
            ->pending()
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->attempts >= 5) {
            return false;
        }

        if (! Hash::check($code, $otp->otp_code)) {
            $otp->increment('attempts');
            return false;
        }

        $otp->update([
            'used_at' => now(),
            'attempts' => $otp->attempts + 1,
        ]);

        return true;
    }

    public function invalidatePrevious(User $user): void
    {
        EmailOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }
}
