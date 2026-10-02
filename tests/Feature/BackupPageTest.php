<?php

namespace Tests\Feature;

use App\Console\Commands\Traits\ResolvesBinaries;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupPageTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_FILE = 'alalay_2026-09-29_11-14-56.sql.gz.enc';

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function mysqldumpUsable(): bool
    {
        $probe = new class {
            use ResolvesBinaries;

            public function path(): string
            {
                return $this->resolveBinary('mysqldump');
            }
        };
        $resolved = $probe->path();

        if (is_file($resolved)) {
            return true;
        }

        $found = trim((string) shell_exec('command -v mysqldump 2>/dev/null'));
        return $found !== '';
    }

    public function test_non_admin_roles_are_forbidden_on_all_backup_routes(): void
    {
        Storage::fake('supabase-backups');

        foreach (['aics_staff', 'mswdo', 'accountant'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get('/admin/backups')->assertForbidden();
            $this->actingAs($user)->post('/admin/backups/run')->assertForbidden();
            $this->actingAs($user)->post('/admin/backups/restore', ['file' => self::VALID_FILE])->assertForbidden();
            $this->actingAs($user)->delete('/admin/backups/' . self::VALID_FILE)->assertForbidden();
        }

        $this->assertSame(0, AuditLog::count());
    }

    public function test_admin_index_renders_with_files_and_filters_props(): void
    {
        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $dir = sys_get_temp_dir() . '/alalay_e1_index_' . uniqid();
        mkdir($dir, 0755, true);
        config(['backup.path' => $dir]);
        file_put_contents($dir . '/' . self::VALID_FILE, str_repeat('x', 200));

        $response = $this->actingAs($admin)->get('/admin/backups?search=alalay&from=2026-09-01&to=2026-09-30');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups')
            ->where('filters', ['search' => 'alalay', 'from' => '2026-09-01', 'to' => '2026-09-30'])
        );

        // `files` is deferred — fetch it via partial reload like the client does.
        $version = config('app.asset_url')
            ? hash('xxh128', (string) config('app.asset_url'))
            : (file_exists($manifest = public_path('build/manifest.json')) ? hash_file('xxh128', $manifest) : null);

        $partial = $this->actingAs($admin)->get('/admin/backups?search=alalay', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Partial-Component' => 'Admin/Backups',
            'X-Inertia-Partial-Data' => 'files',
        ]);
        $partial->assertOk();
        $payload = json_decode($partial->getContent(), true);
        $rows = $payload['props']['files']['data'] ?? [];

        $this->assertCount(1, $rows);
        $this->assertSame(self::VALID_FILE, $rows[0]['file']);
        $this->assertSame('local', $rows[0]['location']);
        // created_at must be a 'Y-m-d H:i:s' string that lands in 2026 —
        // raw Unix seconds were rendered as milliseconds by dayjs (Jan 1970).
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $rows[0]['created_at']);
        $this->assertStringStartsWith('2026-', $rows[0]['created_at']);

        @unlink($dir . '/' . self::VALID_FILE);
        @rmdir($dir);
    }

    public function test_run_with_dead_db_port_flashes_error_and_logs_backup_failed(): void
    {
        if (!$this->mysqldumpUsable()) {
            $this->markTestSkipped('mysqldump not usable in this environment.');
        }

        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $dir = sys_get_temp_dir() . '/alalay_e1_run_' . uniqid();
        mkdir($dir, 0755, true);
        config([
            'backup.path' => $dir,
            'database.connections.mysql.port' => 59999,
        ]);

        $response = $this->actingAs($admin)->post('/admin/backups/run');
        $result = session('backup_result');
        $this->assertSame('error', $result['status'] ?? null);
        // No generic flash → the global AppLayout watcher never double-toasts.
        $response->assertSessionMissing('error');
        $response->assertSessionMissing('success');
        $this->assertStringContainsString('mysqldump', (string) $result['message']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'backup_failed', 'module' => 'system']);
        // Skip-list works: no generic middleware row for this route.
        $this->assertSame(0, AuditLog::whereIn('action', ['run', 'restore', 'destroy'])->count());

        @rmdir($dir);
    }

    public function test_restore_with_invalid_filename_is_rejected_without_side_effects(): void
    {
        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        foreach (['../../.env', 'not-a-backup.txt', 'alalay_2026-09-29_11-14-56.sql.gz'] as $bad) {
            $this->actingAs($admin)->post('/admin/backups/restore', ['file' => $bad])->assertSessionHasErrors('file');
        }

        $this->assertSame(0, AuditLog::count());
        $this->assertFileDoesNotExist(storage_path('framework/down'));
    }

    public function test_location_filter_returns_matching_rows_only(): void
    {
        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $dir = sys_get_temp_dir() . '/alalay_e1_loc_' . uniqid();
        mkdir($dir, 0755, true);
        config(['backup.path' => $dir]);
        $localFile = 'alalay_2026-09-28_10-00-00.sql.gz.enc';
        file_put_contents($dir . '/' . $localFile, str_repeat('x', 200));
        $offsiteFile = 'alalay_2026-09-27_10-00-00.sql.gz.enc';
        Storage::disk('supabase-backups')->put('db/' . $offsiteFile, str_repeat('y', 200));

        $version = config('app.asset_url')
            ? hash('xxh128', (string) config('app.asset_url'))
            : (file_exists($manifest = public_path('build/manifest.json')) ? hash_file('xxh128', $manifest) : null);

        $fetch = function (string $locationQuery) use ($admin, $version): array {
            $qs = http_build_query(array_filter(['location' => $locationQuery]));
            $response = $this->actingAs($admin)->get('/admin/backups' . ($qs ? '?' . $qs : ''), [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) $version,
                'X-Inertia-Partial-Component' => 'Admin/Backups',
                'X-Inertia-Partial-Data' => 'files',
            ]);
            $response->assertOk();
            $payload = json_decode($response->getContent(), true);
            return array_column($payload['props']['files']['data'] ?? [], 'file');
        };

        // local-only file + offsite-only file exist; no location filter shows both.
        $all = $fetch('');
        $this->assertContains($localFile, $all);
        $this->assertContains($offsiteFile, $all);

        $localRows = $fetch('local');
        $this->assertContains($localFile, $localRows);
        $this->assertNotContains($offsiteFile, $localRows);

        $offsiteRows = $fetch('offsite');
        $this->assertContains($offsiteFile, $offsiteRows);
        $this->assertNotContains($localFile, $offsiteRows);

        // filters prop carries the location value so the Select re-selects it.
        $index = $this->actingAs($admin)->get('/admin/backups?location=offsite');
        $index->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Backups')
            ->where('filters.location', 'offsite'));

        @unlink($dir . '/' . $localFile);
        @rmdir($dir);
    }

    public function test_prune_command_removes_expired_local_and_offsite_backups(): void
    {
        Storage::fake('supabase-backups');

        $dir = sys_get_temp_dir() . '/alalay_e1_prune_' . uniqid();
        mkdir($dir, 0755, true);
        config(['backup.path' => $dir, 'backup.retention_days' => 0]);

        $oldFile = 'alalay_2026-09-28_10-00-00.sql.gz.enc';
        $newFile = 'alalay_2026-09-29_10-00-00.sql.gz.enc';
        file_put_contents($dir . '/' . $oldFile, str_repeat('x', 200));
        file_put_contents($dir . '/' . $newFile, str_repeat('x', 200));
        Storage::disk('supabase-backups')->put('db/' . $oldFile, str_repeat('y', 200));
        Storage::disk('supabase-backups')->put('db/' . $newFile, str_repeat('y', 200));

        // Deterministic mtimes: old = yesterday (expired), new = future (kept).
        touch($dir . '/' . $oldFile, now()->subDay()->timestamp);
        touch($dir . '/' . $newFile, now()->addDay()->timestamp);
        touch(Storage::disk('supabase-backups')->path('db/' . $oldFile), now()->subDay()->timestamp);
        touch(Storage::disk('supabase-backups')->path('db/' . $newFile), now()->addDay()->timestamp);

        // Dry-run must not delete anything.
        $this->artisan('backup:prune', ['--dry-run' => true])->assertExitCode(0);
        $this->assertFileExists($dir . '/' . $oldFile);
        Storage::disk('supabase-backups')->assertExists('db/' . $oldFile);

        $this->artisan('backup:prune')->assertExitCode(0);

        $this->assertFileDoesNotExist($dir . '/' . $oldFile);
        $this->assertFileExists($dir . '/' . $newFile);
        Storage::disk('supabase-backups')->assertMissing('db/' . $oldFile);
        Storage::disk('supabase-backups')->assertExists('db/' . $newFile);

        @unlink($dir . '/' . $newFile);
        @rmdir($dir);
    }

    public function test_destroy_removes_local_and_offsite_and_logs_backup_deleted(): void
    {
        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $dir = sys_get_temp_dir() . '/alalay_e1_destroy_' . uniqid();
        mkdir($dir, 0755, true);
        config(['backup.path' => $dir]);
        file_put_contents($dir . '/' . self::VALID_FILE, str_repeat('x', 200));
        Storage::disk('supabase-backups')->put('db/' . self::VALID_FILE, str_repeat('y', 200));

        $response = $this->actingAs($admin)->delete('/admin/backups/' . self::VALID_FILE);
        $result = session('backup_result');
        $this->assertSame('success', $result['status'] ?? null);
        $response->assertSessionMissing('success');
        $response->assertSessionMissing('error');

        $this->assertFileDoesNotExist($dir . '/' . self::VALID_FILE);
        Storage::disk('supabase-backups')->assertMissing('db/' . self::VALID_FILE);

        $log = AuditLog::where('action', 'backup_deleted')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString(self::VALID_FILE, $log->description);
        $this->assertStringContainsString('local + offsite', $log->description);

        @rmdir($dir);
    }

    public function test_run_rejects_invalid_destination(): void
    {
        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post('/admin/backups/run', ['destination' => 'everywhere'])
            ->assertSessionHasErrors('destination');

        $this->assertSame(0, AuditLog::count());

        // Command-level guard: refuses before touching dump/lock/audit.
        $this->assertSame(1, Artisan::call('backup:run', ['--destination' => 'bogus']));
        $this->assertSame(0, AuditLog::count());
    }

    public function test_run_accepts_valid_destination_and_passes_it_through(): void
    {
        if (!$this->mysqldumpUsable()) {
            $this->markTestSkipped('mysqldump not usable in this environment.');
        }

        Storage::fake('supabase-backups');
        $admin = $this->makeUser('admin');

        $dir = sys_get_temp_dir() . '/alalay_e1_dest_' . uniqid();
        mkdir($dir, 0755, true);
        config([
            'backup.path' => $dir,
            'database.connections.mysql.port' => 59999,
        ]);

        // Valid value → no 422; the command runs with --destination=local
        // (the dump itself fails on the dead port — expected here).
        $response = $this->actingAs($admin)->post('/admin/backups/run', ['destination' => 'local']);
        $response->assertSessionHasNoErrors();
        $result = session('backup_result');
        $this->assertSame('error', $result['status'] ?? null);

        @rmdir($dir);
    }
}
