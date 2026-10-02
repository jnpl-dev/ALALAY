<?php

namespace App\Services;

/**
 * Evaluator OTP bypass (config/otp.php). When OTP_BYPASS is enabled the
 * static bypass code is accepted in place of a real one-time code on both
 * OTP flows. Always returns false when the flag is off or no code is set.
 */
class OtpBypass
{
    public static function enabled(): bool
    {
        $code = config('otp.bypass_code');

        return (bool) config('otp.bypass_enabled')
            && is_string($code)
            && $code !== '';
    }

    public static function matches(?string $code): bool
    {
        if (! self::enabled() || ! is_string($code)) {
            return false;
        }

        return hash_equals((string) config('otp.bypass_code'), $code);
    }
}
