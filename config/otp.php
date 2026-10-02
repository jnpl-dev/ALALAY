<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Evaluator Bypass
    |--------------------------------------------------------------------------
    |
    | When enabled, the static bypass code is accepted in place of a real
    | one-time code on both OTP flows (login email OTP and public tracking
    | SMS/email OTP). Intended only for technical evaluation so evaluators
    | can reach staff panels when live mail/SMS delivery is unavailable.
    |
    | Keep OTP_BYPASS=false (or unset) outside evaluation windows — every
    | bypassed verification is still written to the audit log.
    */

    'bypass_enabled' => (bool) env('OTP_BYPASS', false),

    'bypass_code' => env('OTP_BYPASS_CODE'),
];
