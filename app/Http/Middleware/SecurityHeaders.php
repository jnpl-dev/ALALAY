<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=self, microphone=(), geolocation=()');

        $viteOrigins = app()->environment('local')
            ? 'http://localhost:5173 http://127.0.0.1:5173'
            : '';
        $viteWsOrigins = $viteOrigins
            ? 'ws://localhost:5173 ws://127.0.0.1:5173'
            : '';

        $scriptSrc = "'self' 'unsafe-inline'" . ($viteOrigins ? " $viteOrigins" : '');
        $styleSrc = "'self' 'unsafe-inline'" . ($viteOrigins ? " $viteOrigins" : '');
        $fontSrc = "'self' data:" . ($viteOrigins ? " $viteOrigins" : '');
        $connectSrc = "'self' https://*.supabase.co https://psgc.gitlab.io" . ($viteWsOrigins ? " $viteWsOrigins" : '');

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src $scriptSrc",
            "style-src $styleSrc",
            "font-src $fontSrc",
            "img-src 'self' data: blob:",
            "media-src 'self' blob:",
            "connect-src $connectSrc",
            "object-src 'none'",
            "frame-ancestors 'none'",
        ]));

        return $response;
    }
}
