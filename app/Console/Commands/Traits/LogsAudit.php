<?php

namespace App\Console\Commands\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

trait LogsAudit
{
    /**
     * Write an audit row for backup/restore activity. Console runs have no
     * auth user or IP — both columns are nullable. Never let audit failures
     * break the backup itself.
     */
    protected function logAudit(string $action, string $description): void
    {
        try {
            $user = auth()->user();
            AuditLog::create([
                'user_id' => $user?->id,
                'role' => $user?->role,
                'module' => 'system',
                'action' => $action,
                'description' => $description,
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Backup audit log write failed.', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }
}
