<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\LogsAudit;
use App\Services\BackupFileRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    use LogsAudit;

    protected $signature = 'backup:run {--destination=both : Backup destination: local, offsite, or both}';

    protected $description = 'Create encrypted database backup and upload to Supabase';

    public function __construct(private readonly BackupFileRepository $repo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $destination = (string) $this->option('destination');
        if (!in_array($destination, ['local', 'offsite', 'both'], true)) {
            $this->error("Invalid destination \"{$destination}\" — expected local, offsite, or both.");
            return self::FAILURE;
        }

        $backupPath = config('backup.path');
        $encryptPass = config('backup.encrypt_pass');

        if (!$encryptPass) {
            $this->error('BACKUP_ENCRYPT_PASS is not set');
            $this->logAudit('backup_failed', 'Database backup failed: BACKUP_ENCRYPT_PASS is not set.');
            return self::FAILURE;
        }

        $lock = Cache::lock('backup', 600);

        if (!$lock->get()) {
            $this->warn('Another backup or restore is already in progress — skipping.');
            Log::info('Backup skipped: lock held by another process.');
            return self::SUCCESS;
        }

        try {
            return $this->runBackup($backupPath, $encryptPass, $destination);
        } finally {
            $lock->release();
        }
    }

    protected function runBackup(string $backupPath, string $encryptPass, string $destination = 'both'): int
    {
        $this->info('Dumping database...');

        try {
            $snapshot = $this->repo->snapshot($destination);
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Database backup failed.', ['error' => $e->getMessage()]);
            $this->logAudit('backup_failed', 'Database backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $filename = $snapshot['file'];
        $uploaded = $snapshot['uploaded'];

        if ($destination !== 'local' && !$uploaded && config('backup.supabase_key')) {
            $this->warn('Offsite upload failed: ' . ($snapshot['upload_error'] ?? 'unknown error'));
            Log::error('Supabase backup upload failed.', ['file' => $filename, 'error' => $snapshot['upload_error']]);
        }

        if ($destination === 'offsite' && $uploaded) {
            $this->info('Offsite upload complete (local copy removed).');
        } else {
            $this->info('Local backup saved: ' . $filename . ' (' . number_format($snapshot['size']) . ' bytes)');
            if ($uploaded) {
                $this->info('Offsite upload complete.');
            }
        }

        $this->pruneOldBackups($backupPath);

        if ($uploaded) {
            $this->pruneSupabase();
        }

        Log::info('Database backup completed.', ['file' => $filename, 'uploaded' => $uploaded, 'destination' => $destination]);
        $this->info('Backup complete.');

        // 'local' never fails on upload (it is intentionally skipped);
        // 'offsite'/'both' still require the offsite copy to succeed.
        $success = $destination === 'local' || $uploaded;

        $size = number_format($snapshot['size']);
        if ($success) {
            $label = match ($destination) {
                'local' => 'local only',
                'offsite' => 'offsite only',
                default => 'local + Supabase',
            };
            $this->logAudit('backup_created', "Created database backup {$filename} ({$size} bytes) — {$label}.");
        } else {
            $this->logAudit('backup_failed', "Database backup failed: offsite upload failed (local copy saved as {$filename}, {$size} bytes).");
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }

    protected function pruneOldBackups(string $backupPath): void
    {
        $retention = (int) config('backup.retention_days', 30);
        $cutoff = now()->subDays($retention)->timestamp;

        $files = glob(rtrim($backupPath, '/\\') . '/alalay_*.sql.gz.enc');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $this->info('Pruned: ' . basename($file));
            }
        }
    }

    protected function pruneSupabase(): void
    {
        $key = config('backup.supabase_key');
        $secret = config('backup.supabase_secret');

        if (!$key || !$secret) {
            return;
        }

        $retention = (int) config('backup.retention_days', 30);
        $cutoff = now()->subDays($retention);

        try {
            $disk = Storage::disk('supabase-backups');
            $files = $disk->files('db');

            foreach ($files as $file) {
                if (!str_starts_with(basename($file), 'alalay_')) {
                    continue;
                }
                $lastModified = $disk->lastModified($file);
                if ($lastModified !== false && $lastModified < $cutoff->getTimestamp()) {
                    $disk->delete($file);
                    $this->info('Pruned offsite: ' . basename($file));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Offsite backup pruning failed.', ['error' => $e->getMessage()]);
        }
    }
}
