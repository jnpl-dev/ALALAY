<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public function log(
        string $action,
        string $module,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
    ): AuditLog {
        $user = request()->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
