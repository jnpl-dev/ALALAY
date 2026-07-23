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

        $viteDev = app()->environment('local') ? 'http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173' : '';

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' $viteDev",
            "worker-src 'self' blob: $viteDev",
            "style-src 'self' 'unsafe-inline' $viteDev",
            "font-src 'self' data: $viteDev",
            "img-src 'self' data: blob: https://*.supabase.co",
            "media-src 'self' blob:",
            "connect-src 'self' https://*.supabase.co https://psgc.gitlab.io $viteDev",
            "frame-src 'self' blob: https://*.supabase.co $viteDev",
            "object-src 'none'",
            "frame-ancestors 'none'",
        ]));

        return $response;
    }
}
