<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\BackupFileRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class BackupController extends Controller
{
    private const FILE_RULES = [
        'required',
        'string',
        'regex:/\Aalalay_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz\.enc\z/',
    ];

    public function __construct(private readonly BackupFileRepository $repo)
    {
    }

    /**
     * Dedicated flash key for backup actions (not the generic success/error
     * ones the global AppLayout watcher toasts) — Backups.vue toasts it
     * explicitly per action, so there is no double-toast.
     */
    private static function result(string $status, string $message): RedirectResponse
    {
        // Artisan output is multi-line; toast details render on one line.
        $message = str_replace(["\r\n", "\n"], ' · ', $message);

        return redirect()->back()->with('backup_result', [
            'status' => $status,
            'message' => $message,
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/Backups', [
            'files' => Inertia::defer(fn () => $this->repo->paginate(
                $request->query('search'),
                $request->query('from'),
                $request->query('to'),
                $request->integer('page', 1),
                $request->query('location')
            )),
            'filters' => $request->only(['search', 'from', 'to', 'location']),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $validated = $request->validate([
            'destination' => ['sometimes', 'string', 'in:local,offsite,both'],
        ]);
        $destination = $validated['destination'] ?? 'both';

        $exit = Artisan::call('backup:run', ['--destination' => $destination]);
        $output = trim(Artisan::output());

        return $exit === 0
            ? self::result('success', $output ?: 'Backup complete.')
            : self::result('error', $output ?: 'Backup failed. Check laravel.log for details.');
    }

    public function restore(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $validated = $request->validate(['file' => self::FILE_RULES]);
        $file = basename($validated['file']);

        $exit = Artisan::call('backup:restore', ['file' => $file]);
        $output = trim(Artisan::output());

        return $exit === 0
            ? self::result('success', $output ?: 'Restore complete.')
            : self::result('error', $output ?: 'Restore failed. The system may still be in maintenance mode.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $request->merge(['file' => $request->route('file')]);
        $validated = $request->validate(['file' => self::FILE_RULES]);
        $file = basename($validated['file']);

        $removed = $this->repo->delete($file);

        if (!$removed['local'] && !$removed['offsite']) {
            return self::result('error', "Backup {$file} not found — nothing was deleted.");
        }

        $locations = implode(' + ', array_filter([
            $removed['local'] ? 'local' : null,
            $removed['offsite'] ? 'offsite' : null,
        ]));

        AuditLog::create([
            'user_id' => auth()->id(),
            'role' => auth()->user()?->role,
            'module' => 'system',
            'action' => 'backup_deleted',
            'description' => "Deleted backup {$file} ({$locations}).",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return self::result('success', "Deleted backup {$file} ({$locations}).");
    }
}
