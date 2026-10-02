<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\InteractsWithMysql;
use App\Console\Commands\Traits\LogsAudit;
use App\Console\Commands\Traits\ResolvesBinaries;
use App\Services\BackupFileRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreBackup extends Command
{
    use InteractsWithMysql, LogsAudit, ResolvesBinaries;

    protected $signature = 'backup:restore {file : Backup filename, e.g. alalay_2026-09-29_02-00-00.sql.gz.enc}';

    protected $description = 'Restore the database from a backup (pre-restore snapshot + maintenance mode)';

    public function __construct(private readonly BackupFileRepository $repo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Defense in depth: same allowlist the controller validates against.
        $file = basename((string) $this->argument('file'));
        if (!$this->repo->isValidFilename($file)) {
            $this->error('Invalid backup filename.');
            $this->logAudit('backup_restore_failed', "Restore rejected: invalid backup filename. No changes were made.");
            return self::FAILURE;
        }

        $lock = Cache::lock('backup', 600);
        if (!$lock->get()) {
            $this->error('Another backup or restore is already in progress.');
            $this->logAudit('backup_restore_failed', "Restore of {$file} rejected: another backup or restore holds the lock. No changes were made.");
            return self::FAILURE;
        }

        $broughtDown = false;
        $snapshotFile = null;

        try {
            $this->info("Locating backup {$file}...");
            $source = $this->repo->locate($file);
            if ($source === null) {
                $this->error('Backup not found locally or on Supabase.');
                $this->logAudit('backup_restore_failed', "Restore failed: {$file} not found in local or offsite storage. No changes were made.");
                return self::FAILURE;
            }

            // Pre-restore snapshot — the rollback net.
            $this->info('Creating pre-restore snapshot...');
            try {
                $snapshot = $this->repo->snapshot();
                $snapshotFile = $snapshot['file'];
                $this->info("Snapshot saved: {$snapshotFile} (" . number_format($snapshot['size']) . ' bytes).');
                if (!$snapshot['uploaded'] && config('backup.supabase_key')) {
                    $this->warn('Snapshot offsite upload failed — proceeding with the local snapshot as rollback net. (' . ($snapshot['upload_error'] ?? 'unknown error') . ')');
                    Log::warning('Snapshot offsite upload failed; restore proceeds.', ['snapshot' => $snapshotFile, 'error' => $snapshot['upload_error']]);
                }
            } catch (\Throwable $e) {
                $this->error('Snapshot creation failed — ABORTING, nothing was changed.');
                $this->error($e->getMessage());
                $this->logAudit('backup_restore_failed', "Restore aborted: pre-restore snapshot failed ({$e->getMessage()}). No changes were made.");
                return self::FAILURE;
            }

            if (!app()->isDownForMaintenance()) {
                Artisan::call('down', [
                    '--render' => 'errors.503',
                    '--secret' => config('app.maintenance_secret'),
                ]);
                $broughtDown = true;
                $this->info('System placed in maintenance mode.');
            } else {
                $this->info('System already in maintenance mode.');
            }

            $failure = $this->importFrom($source);

            // The import replaces audit_logs with the backup's contents, so the
            // snapshot's audit row is written after the import to survive it.
            $this->logAudit('backup_created', "Created pre-restore snapshot {$snapshotFile} (" . number_format($snapshot['size']) . " bytes) before restoring from {$file}.");

            if ($failure !== null) {
                $this->error($failure);
                $this->logAudit('backup_restore_failed', "Restore failed: {$failure} System left in maintenance mode — roll back from snapshot {$snapshotFile}.");
                return self::FAILURE;
            }

            if ($broughtDown) {
                Artisan::call('up');
                $this->info('System brought back online.');
            }

            $this->logAudit('backup_restored', "Restored database from {$file}; safety snapshot {$snapshotFile} created before restore.");
            $this->info('Restore complete.');
            return self::SUCCESS;
        } finally {
            // The import restores the cache_locks table from the dump, which can
            // resurrect a stale lock row with an older owner — release() matches
            // on owner and would silently leave it behind, blocking future runs.
            // We are the only legitimate holder during the restore, so clear any
            // row for our key first; release() then removes ours (a fresh
            // concurrent acquirer's row survives — its owner won't match).
            DB::table('cache_locks')->where('key', config('cache.prefix') . 'backup')->delete();
            $lock->release();
        }
    }

    /**
     * decrypt → gunzip → import. Returns null on success or a failure reason.
     * Temp files are always removed.
     *
     * Known limitation (accepted): the dump's `DROP TABLE IF EXISTS` only
     * recreates tables present in the backup — tables created after that
     * backup are not removed. Not a DROP DATABASE wipe; fine for this app.
     */
    private function importFrom(string $source): ?string
    {
        $encryptPass = config('backup.encrypt_pass');
        if (!$encryptPass) {
            return 'BACKUP_ENCRYPT_PASS is not set.';
        }

        $decryptedFile = $source . '.decrypted';
        $sqlFile = $source . '.sql';
        $gzerrFile = $source . '.gzerr';

        try {
            $bashPass = str_replace("'", "'\\''", $encryptPass);
            $bashSource = str_replace('\\', '/', $source);
            $bashDecrypted = str_replace('\\', '/', $decryptedFile);

            exec('bash -c ' . escapeshellarg(
                "openssl enc -d -aes-256-cbc -pbkdf2 -pass pass:'{$bashPass}'"
                . " -in '{$bashSource}'"
                . " -out '{$bashDecrypted}'"
                . ' 2>&1'
            ), $out1, $exit1);
            if ($exit1 !== 0 || !is_file($decryptedFile) || filesize($decryptedFile) === 0) {
                return 'Decryption failed (exit ' . $exit1 . '): ' . (implode(' ', $out1) ?: 'no output');
            }

            $bashSql = str_replace('\\', '/', $sqlFile);
            $bashGzerr = str_replace('\\', '/', $gzerrFile);
            exec('bash -c ' . escapeshellarg(
                "gunzip -c '{$bashDecrypted}' > '{$bashSql}' 2> '{$bashGzerr}'"
            ), $out2, $exit2);
            $gzerr = is_file($gzerrFile) ? trim((string) file_get_contents($gzerrFile)) : '';
            if ($exit2 !== 0 || !is_file($sqlFile) || filesize($sqlFile) === 0) {
                $detail = trim(implode(' ', array_merge($out2, $gzerr !== '' ? [$gzerr] : [])));
                return 'Decompression failed (exit ' . $exit2 . '): ' . ($detail ?: 'no output');
            }

            $db = config('database.connections.mysql');
            $importCmd = sprintf(
                '%s -h %s -P %s -u %s %s < %s 2>&1',
                escapeshellarg($this->resolveBinary('mysql')),
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                escapeshellarg($db['database']),
                escapeshellarg($sqlFile)
            );
            $this->withMysqlPassword((string) ($db['password'] ?? ''), function () use ($importCmd, &$out3, &$exit3) {
                exec($importCmd, $out3, $exit3);
            });
            if ($exit3 !== 0) {
                return 'MySQL import failed (exit ' . $exit3 . '): ' . (implode("\n", $out3) ?: 'no output');
            }

            return null;
        } finally {
            @unlink($decryptedFile);
            @unlink($sqlFile);
            @unlink($gzerrFile);
        }
    }
}
