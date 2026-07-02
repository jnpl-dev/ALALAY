<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        $user = $request->user();
        if (! $user) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName() ?? '';

        $parts = explode('.', $routeName);
        $module = $parts[0] ?? 'general';
        $action = $parts[1] ?? $routeName;

        AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'module' => $module,
            'action' => $action,
            'description' => sprintf('%s %s %s', $user->full_name, $request->method(), $routeName),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return $response;
    }
}
