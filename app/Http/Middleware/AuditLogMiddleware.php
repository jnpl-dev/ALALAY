<?php

namespace App\Http\Middleware;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AssistanceCodeReference;
use App\Models\AuditLog;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Models\Voucher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Routes that already write their own AuditLog row directly in the controller,
     * so the middleware must not double-log them.
     */
    private const SKIP_ROUTES = [
        'logout',
        'otp.verify',
        'aup.accept',
        'admin.maintenance.toggle',
    ];

    private const ENTITY_MODEL_MAP = [
        'user' => User::class,
        'application' => Application::class,
        'voucher' => Voucher::class,
        'category' => AssistanceCategory::class,
        'required_document' => RequiredDocument::class,
        'assistance_code_reference' => AssistanceCodeReference::class,
    ];

    private const ACTION_VERBS = [
        'store' => 'created',
        'update' => 'updated',
        'destroy' => 'deleted',
        'toggle-status' => 'changed the status of',
        'revoke-sessions' => 'revoked the active sessions of',
        'hold' => 'placed on hold',
        'release-hold' => 'released the hold on',
        'acknowledge' => 'acknowledged',
        'claim' => 'claimed',
        'approve' => 'approved',
        'return' => 'returned',
    ];

    /**
     * Route-name keyed description templates. {ref} is replaced with the
     * entity's human identifier (application reference code, user name, etc.).
     */
    private const DESCRIPTION_TEMPLATES = [
        'aics.applications.approve' => 'approved application {ref}',
        'aics.applications.return' => 'returned application {ref}',
        'aics.applications.store-assisted' => 'encoded a new assisted application',
        'aics.assistance-codes.store' => 'coded assistance for application {ref}',
        'mswdo.applications.approve' => 'approved application {ref}',
        'mswdo.applications.return' => 'returned application {ref}',
        'mswdo.vouchers.store' => 'created a voucher for application {ref}',
        'accountant.vouchers.approve' => 'approved the voucher for application {ref}',
        'internal-audit.applications.approve' => 'approved application {ref}',
        'internal-audit.applications.return' => 'returned application {ref}',
        'budget-office.vouchers.approve' => 'approved the voucher for application {ref}',
        'budget-office.vouchers.hold' => 'placed the voucher for application {ref} on hold',
        'budget-office.vouchers.release-hold' => 'released the voucher for application {ref} from hold',
        'treasurer.cheques.acknowledge' => 'acknowledged the cheque for application {ref}',
        'treasurer.cheques.claim' => 'claimed the cheque for application {ref}',
        'admin.users.toggle-status' => 'changed the status of user {ref}',
        'admin.users.revoke-sessions' => 'revoked the active sessions of user {ref}',
        'admin.settings.update' => 'updated system settings',
        'admin.sms.claiming.template' => 'updated the SMS claiming template',
        'admin.sms.claiming.trigger' => 'triggered SMS claiming messages',
        'account.update' => 'updated their account details',
    ];

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

        $routeName = $request->route()?->getName() ?? '';
        if (in_array($routeName, self::SKIP_ROUTES, true)) {
            return $response;
        }

        $parts = explode('.', $routeName);
        $module = $parts[0] ?? 'general';
        $action = count($parts) > 1 ? end($parts) : $routeName;

        [$entityType, $entityId, $entity] = $this->resolveEntity($request);

        $description = $this->buildDescription(
            $user,
            $routeName,
            $module,
            $action,
            $entityType,
            $entity,
        );

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

    private function resolveEntity(Request $request): array
    {
        $routeParams = $request->route()?->parameters() ?? [];

        foreach (self::ENTITY_MODEL_MAP as $paramName => $modelClass) {
            if (! isset($routeParams[$paramName]) || $routeParams[$paramName] === null) {
                continue;
            }

$value = $routeParams[$paramName];

            $entity = is_object($value) ? $value : $modelClass::query()->find($value);

            return [
                class_basename($modelClass),
                $entity?->getKey() ?? $value,
                $entity,
            ];
        }

        return [null, null, null];
    }

    private function buildDescription(
        User $user,
        string $routeName,
        string $module,
        string $action,
        ?string $entityType,
        $entity,
    ): string {
        $ref = $this->entityReference($entityType, $entity);
        $entityDescription = $this->entityDescription($entityType, $ref);

        $template = self::DESCRIPTION_TEMPLATES[$routeName] ?? null;

        if ($template) {
            $text = trim(str_replace(' {ref}', ' {ref}', $template));
            if ($ref) {
                $text = str_replace('{ref}', $ref, $text);
            } else {
                $text = trim(preg_replace('/\s*\{ref\}/', '', $text));
            }
        } elseif ($entityDescription) {
            $verb = self::ACTION_VERBS[$action] ?? 'performed an action on';
            $text = sprintf('%s %s', $verb, $entityDescription);
        } elseif ($resource = $this->resourceNoun($module, $routeName)) {
            $verb = self::ACTION_VERBS[$action] ?? 'performed an action on';
            $text = sprintf('%s %s', $verb, $this->withArticle($resource));
        } else {
            $text = sprintf('%s in %s', Str::replace('-', ' ', $action), $module);
        }

        return trim(preg_replace('/\s+/', ' ', sprintf('%s %s', $user->full_name, $text)));
    }

    private function entityReference(?string $entityType, $entity): ?string
    {
        if (! $entity) {
            return null;
        }

        return match ($entityType) {
            'Application' => $entity->reference_code ?: null,
            'Voucher' => $entity->application?->reference_code ?: null,
            'User' => $entity->full_name ?: null,
            'AssistanceCategory' => $entity->category_name ?: null,
            'RequiredDocument' => $entity->doc_name ?: null,
            'AssistanceCodeReference' => $entity->code_type ?: null,
            default => null,
        };
    }

    private function entityDescription(?string $entityType, ?string $ref): ?string
    {
        if ($ref) {
            return match ($entityType) {
                'Application' => "application {$ref}",
                'Voucher' => "voucher for application {$ref}",
                'User' => "user {$ref}",
                'AssistanceCategory' => "assistance category {$ref}",
                'RequiredDocument' => "required document {$ref}",
                'AssistanceCodeReference' => "assistance code reference {$ref}",
                default => $entityType,
            };
        }

        return $entityType ? strtolower($entityType) : null;
    }

    private function entityNoun(?string $entityType): ?string
    {
        return $entityType ? strtolower($entityType) : null;
    }

    private function resourceNoun(string $module, string $routeName): ?string
    {
        $parts = explode('.', $routeName);
        if (count($parts) < 3) {
            return null;
        }

        $segments = array_slice($parts, 1, -1);
        if (count($segments) !== 1) {
            return null;
        }

        $resource = $segments[0];
        $singular = Str::singular($resource);

        if ($singular === $resource && ! in_array($resource, ['users', 'vouchers'])) {
            return null;
        }

        return $singular;
    }

    private function withArticle(string $noun): string
    {
        $lowerNoun = strtolower($noun);

        if (in_array($lowerNoun, ['user'])) {
            return "a {$noun}";
        }

        return in_array($lowerNoun[0], ['a', 'e', 'i', 'o', 'u'])
            ? "an {$noun}"
            : "a {$noun}";
    }
}