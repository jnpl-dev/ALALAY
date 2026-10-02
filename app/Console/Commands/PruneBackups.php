<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Standalone retention sweep: delete local + offsite backups older than
 * BACKUP_RETENTION_DAYS. Scheduled weekly so cleanup does not depend on
 * `backup:run` succeeding first (belt-and-braces beside the prune inside
 * backup:run).
 */
class PruneBackups extends Command
{
    protected $signature = 'backup:prune {--dry-run : List what would be deleted without deleting}';

    protected $description = 'Delete local and offsite backups older than BACKUP_RETENTION_DAYS';

    public function handle(): int
    {
        $retention = (int) config('backup.retention_days', 30);
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($retention)->timestamp;

        $this->info(sprintf('Retention: %d days (cutoff %s)%s', $retention, date('Y-m-d H:i:s', $cutoff), $dryRun ? ' [dry-run]' : ''));

        $deleted = 0;
        $failed = 0;

        // Local
        foreach (glob(rtrim(config('backup.path'), '/\\') . '/alalay_*.sql.gz.enc') ?: [] as $file) {
            if (filemtime($file) >= $cutoff) {
                continue;
            }
            if ($dryRun) {
                $this->line('Would prune: ' . basename($file));
                continue;
            }
            if (@unlink($file)) {
                $this->info('Pruned: ' . basename($file));
                $deleted++;
            } else {
                $this->warn('Failed to prune: ' . basename($file));
                $failed++;
            }
        }

        // Offsite
        if (config('backup.supabase_key') && config('backup.supabase_secret')) {
            try {
                $disk = Storage::disk('supabase-backups');
                foreach ($disk->files('db') as $path) {
                    if (!str_starts_with(basename($path), 'alalay_')) {
                        continue;
                    }
                    $lastModified = $disk->lastModified($path);
                    if ($lastModified === false || $lastModified >= $cutoff) {
                        continue;
                    }
                    if ($dryRun) {
                        $this->line('Would prune offsite: ' . basename($path));
                        continue;
                    }
                    $disk->delete($path);
                    $this->info('Pruned offsite: ' . basename($path));
                    $deleted++;
                }
            } catch (\Throwable $e) {
                Log::warning('backup:prune offsite sweep failed.', ['error' => $e->getMessage()]);
                $this->warn('Offsite sweep failed: ' . $e->getMessage());
                $failed++;
            }
        } else {
            $this->line('Supabase not configured — skipping offsite sweep.');
        }

        $this->info(sprintf('%sdone — %d pruned, %d failed.', $dryRun ? '[dry-run] ' : '', $deleted, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
