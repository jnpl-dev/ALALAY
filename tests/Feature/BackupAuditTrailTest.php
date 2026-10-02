<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private const BACKUP_ACTIONS = [
        'backup_created' => 'Backup Created',
        'backup_failed' => 'Backup Failed',
        'backup_verified' => 'Backup Verified',
        'backup_verify_failed' => 'Backup Verify Failed',
        'backup_restored' => 'Backup Restored',
        'backup_restore_failed' => 'Backup Restore Failed',
        'backup_deleted' => 'Backup Deleted',
    ];

    public function test_backup_audit_rows_render_with_labels_and_appear_in_action_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (self::BACKUP_ACTIONS as $action => $label) {
            AuditLog::create([
                'module' => 'system',
                'action' => $action,
                'description' => "seeded {$action} row",
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/audit-logs');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AuditLogs')
            ->where('actions', fn ($actions) => collect($actions)->sort()->values()->all() === collect(array_keys(self::BACKUP_ACTIONS))->sort()->values()->all())
        );

        // `logs` is an Inertia::defer prop — fetch it via partial reload
        // the way the client does after mount. Replicate the middleware's
        // version hash so the X-Inertia request is not rejected as stale (409).
        $version = config('app.asset_url')
            ? hash('xxh128', (string) config('app.asset_url'))
            : (file_exists($manifest = public_path('build/manifest.json')) ? hash_file('xxh128', $manifest) : null);

        $partial = $this->actingAs($admin)->get('/admin/audit-logs', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Partial-Component' => 'Admin/AuditLogs',
            'X-Inertia-Partial-Data' => 'logs',
        ]);
        $partial->assertOk();
        $payload = json_decode($partial->getContent(), true);
        $labels = collect($payload['props']['logs']['data'] ?? []);

        $this->assertCount(7, $labels);
        $byAction = $labels->pluck('action_label', 'action');
        foreach (self::BACKUP_ACTIONS as $action => $label) {
            $this->assertSame($label, $byAction[$action], "Missing/incorrect label for {$action}");
        }
        $this->assertSame('System', $labels->first()['module_label'] ?? null);
    }

    public function test_audit_page_has_no_duplicate_backup_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'module' => 'system',
            'action' => 'backup_created',
            'description' => 'single row',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();

        $this->assertSame(1, AuditLog::where('action', 'backup_created')->count());
    }
}
