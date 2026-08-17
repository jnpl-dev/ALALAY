<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Configuration for the Cloudflare Turnstile captcha widget.
    |
    | When the secret key is not configured, the Turnstile rule passes through
    | (fail-open) so local development and preview deployments never hard-block.
    */

    'site_key' => env('TURNSTILE_SITE_KEY', ''),

    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),

    'enabled' => env('TURNSTILE_SECRET_KEY') !== null && env('TURNSTILE_SECRET_KEY') !== '',
];