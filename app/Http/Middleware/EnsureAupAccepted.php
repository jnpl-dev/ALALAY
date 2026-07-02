<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAupAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->acceptable_use_policy_accepted_at === null) {
            return redirect()->route('aup.show');
        }

        return $next($request);
    }
}
