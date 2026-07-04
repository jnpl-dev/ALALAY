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

        $entityType = null;
        $entityId = null;

        $routeParams = $route?->parameters() ?? [];
        $entityModelMap = [
            'user' => 'User',
            'application' => 'Application',
            'category' => 'AssistanceCategory',
            'required_document' => 'RequiredDocument',
            'assistance_code_reference' => 'AssistanceCodeReference',
        ];

        foreach ($entityModelMap as $paramName => $modelName) {
            if (isset($routeParams[$paramName]) && is_object($routeParams[$paramName])) {
                $entityType = $modelName;
                $entityId = $routeParams[$paramName]->id;
                break;
            } elseif (isset($routeParams[$paramName])) {
                $entityType = $modelName;
                $entityId = $routeParams[$paramName];
            }
        }

        $actionVerbs = [
            'store' => 'created',
            'update' => 'updated',
            'destroy' => 'deleted',
            'toggle-status' => 'toggled status of',
            'revoke-sessions' => 'revoked sessions of',
            'accept' => 'accepted',
        ];
        $verb = $actionVerbs[$action] ?? $request->method();

        $entityName = $entityType ? str($entityType)->headline() : '';
        $description = $verb === 'POST' || $verb === 'PUT' || $verb === 'PATCH' || $verb === 'DELETE'
            ? sprintf('%s %s %s', $user->full_name, $verb, $entityName ?: $module)
            : sprintf('%s %s %s', $user->full_name, $verb, $entityName ?: $module);

        if ($action === 'toggle-status' || $action === 'revoke-sessions') {
            $description = sprintf('%s %s %s', $user->full_name, $verb, $entityName ?: $module);
        } elseif (in_array($action, ['store', 'update', 'destroy'])) {
            $description = sprintf('%s %s %s', $user->full_name, $verb, $entityName ?: str($module)->singular());
        }

        AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return $response;
    }
}
