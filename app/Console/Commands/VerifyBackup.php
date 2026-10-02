<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\InteractsWithMysql;
use App\Console\Commands\Traits\LogsAudit;
use App\Console\Commands\Traits\ResolvesBinaries;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VerifyBackup extends Command
{
    use InteractsWithMysql, LogsAudit, ResolvesBinaries;

    protected $signature = 'backup:verify';

    protected $description = 'Restore the latest backup to a test database to verify integrity';

    public function handle(): int
    {
        $backupPath = config('backup.path');
        $encryptPass = config('backup.encrypt_pass');
        $testDatabase = config('backup.test_database');
        $db = config('database.connections.mysql');

        if (!$encryptPass) {
            $this->error('BACKUP_ENCRYPT_PASS is not set in config/backup.php');
            $this->logAudit('backup_verify_failed', 'Backup verification failed: BACKUP_ENCRYPT_PASS is not set.');
            return self::FAILURE;
        }

        $files = glob(rtrim($backupPath, '/\\') . '/alalay_*.sql.gz.enc');

        if (empty($files)) {
            $latest = $this->fetchOffsiteBackup($backupPath);
            if ($latest === null) {
                $this->error('No backup files found locally or on Supabase.');
                Log::warning('Backup verification failed: no backup file found.');
                $this->logAudit('backup_verify_failed', 'Backup verification failed: no backup file found locally or on Supabase.');
                return self::FAILURE;
            }
        } else {
            $latest = collect($files)->sortByDesc(fn ($f) => filemtime($f))->first();
        }

        $this->info('Verifying: ' . basename($latest));

        $decryptedFile = $latest . '.decrypted';
        $sqlFile = $latest . '.sql';

        try {
            // Step 1: Decrypt
            $this->info('Step 1: Decrypting...');
            $bashPass = str_replace("'", "'\\''", $encryptPass);
            $bashLatest = str_replace('\\', '/', $latest);
            $bashDecrypted = str_replace('\\', '/', $decryptedFile);
            $cmd1 = 'bash -c ' . escapeshellarg(
                "openssl enc -d -aes-256-cbc -pbkdf2 -pass pass:'{$bashPass}'"
                . " -in '{$bashLatest}'"
                . " -out '{$bashDecrypted}'"
                . ' 2>&1'
            );
            exec($cmd1, $out1, $exit1);
            if ($exit1 !== 0 || !file_exists($decryptedFile) || filesize($decryptedFile) === 0) {
                $this->failVerify('Decryption failed.', $out1, $exit1, $decryptedFile, $sqlFile);
                return self::FAILURE;
            }

            // Step 2: Decompress
            $this->info('Step 2: Decompressing...');
            $bashSql = str_replace('\\', '/', $sqlFile);
            $bashGzerr = str_replace('\\', '/', $sqlFile . '.gzerr');
            $cmd2 = 'bash -c ' . escapeshellarg(
                "gunzip -c '{$bashDecrypted}' > '{$bashSql}' 2> '{$bashGzerr}'"
            );
            exec($cmd2, $out2, $exit2);
            if ($exit2 !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
                $gzerr = is_file($sqlFile . '.gzerr') ? trim((string) file_get_contents($sqlFile . '.gzerr')) : '';
                $this->failVerify('Decompression failed.', array_merge($out2, $gzerr !== '' ? [$gzerr] : []), $exit2, $decryptedFile, $sqlFile);
                return self::FAILURE;
            }
            @unlink($sqlFile . '.gzerr');

            // Step 3: Create test database
            $this->info('Step 3: Preparing test database...');
            $createCmd = sprintf(
                '%s -h %s -P %s -u %s -e %s 2>&1',
                escapeshellarg($this->resolveBinary('mysql')),
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                escapeshellarg('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $testDatabase) . '`')
            );
            $this->withMysqlPassword((string) ($db['password'] ?? ''), function () use ($createCmd, &$outCreate, &$exitCreate) {
                exec($createCmd, $outCreate, $exitCreate);
            });
            if ($exitCreate !== 0) {
                $this->failVerify('Failed to create test database.', $outCreate, $exitCreate, $decryptedFile, $sqlFile);
                return self::FAILURE;
            }

            // Step 4: Import
            $this->info('Step 4: Importing to test database...');
            $cmd4 = sprintf(
                '%s -h %s -P %s -u %s %s < %s 2>&1',
                escapeshellarg($this->resolveBinary('mysql')),
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                escapeshellarg($testDatabase),
                escapeshellarg($sqlFile)
            );
            $this->withMysqlPassword((string) ($db['password'] ?? ''), function () use ($cmd4, &$out4, &$exit4) {
                exec($cmd4, $out4, $exit4);
            });
            if ($exit4 !== 0) {
                $this->failVerify('Import failed.', $out4, $exit4, $decryptedFile, $sqlFile);
                return self::FAILURE;
            }

            // Step 5: Verify row counts
            $this->info('Step 5: Verifying contents...');
            $countCmd = sprintf(
                '%s -h %s -P %s -u %s %s -N -e %s 2>&1',
                escapeshellarg($this->resolveBinary('mysql')),
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                escapeshellarg($testDatabase),
                escapeshellarg('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')
            );
            $raw = (string) $this->withMysqlPassword(
                (string) ($db['password'] ?? ''),
                fn () => shell_exec($countCmd)
            );
            // MYSQL_PWD / -p warnings land in the same stream — keep the last numeric line.
            $numericLines = array_values(array_filter(
                array_map('trim', explode("\n", trim($raw))),
                'is_numeric'
            ));
            $tableCount = $numericLines ? end($numericLines) : trim($raw);

            if (!is_numeric($tableCount) || (int) $tableCount === 0) {
                $this->failVerify('Test database has no tables — import was empty.', ['tables: ' . $tableCount], 1, $decryptedFile, $sqlFile);
                return self::FAILURE;
            }

            $this->info('Backup verified successfully: ' . basename($latest) . ' (' . $tableCount . ' tables restored)');
            Log::info('Backup verification passed.', [
                'file' => basename($latest),
                'tables' => (int) $tableCount,
            ]);
            $this->logAudit('backup_verified', 'Verified backup ' . basename($latest) . ' — ' . $tableCount . ' tables restored.');

            $this->dropTestDatabase($testDatabase, $db);

            // Cleanup temp files
            @unlink($decryptedFile);
            @unlink($sqlFile);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->failVerify('Verification threw an exception.', [$e->getMessage()], 1, $decryptedFile, $sqlFile);

            return self::FAILURE;
        }
    }

    private function dropTestDatabase(string $testDatabase, array $db): void
    {
        $dropCmd = sprintf(
            '%s -h %s -P %s -u %s -e %s 2>&1',
            escapeshellarg($this->resolveBinary('mysql')),
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            escapeshellarg('DROP DATABASE IF EXISTS `' . str_replace('`', '', $testDatabase) . '`')
        );
        $this->withMysqlPassword((string) ($db['password'] ?? ''), function () use ($dropCmd, &$outDrop, &$exitDrop) {
            exec($dropCmd, $outDrop, $exitDrop);
        });

        if ($exitDrop !== 0) {
            // Soft step: the restore itself passed — keep verify exit 0.
            $this->warn('Could not drop test database `' . $testDatabase . '` (kept for inspection).');
            Log::warning('Test database drop failed.', [
                'database' => $testDatabase,
                'exit_code' => $exitDrop,
                'output' => implode("\n", $outDrop),
            ]);
            return;
        }

        $this->info('Test database `' . $testDatabase . '` dropped.');
    }

    private function fetchOffsiteBackup(string $backupPath): ?string
    {
        if (!config('backup.supabase_key') || !config('backup.supabase_secret')) {
            return null;
        }

        $this->info('No local backup found — checking Supabase...');

        try {
            $disk = Storage::disk('supabase-backups');
            $objects = $disk->files('db');
        } catch (\Throwable $e) {
            Log::warning('Offsite backup listing failed.', ['error' => $e->getMessage()]);
            return null;
        }

        $candidates = array_values(array_filter(
            $objects,
            fn (string $f): bool => str_starts_with(basename($f), 'alalay_')
        ));

        if (empty($candidates)) {
            return null;
        }

        try {
            $newest = collect($candidates)
                ->sortByDesc(fn (string $f): int => $disk->lastModified($f) ?: 0)
                ->first();

            $tempDir = rtrim($backupPath, '/\\');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            // Use the original name so the retention prune glob (`alalay_*`) manages it.
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . basename($newest);

            $stream = $disk->readStream($newest);
            if (!$stream) {
                return null;
            }
            $written = file_put_contents($tempPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($written === false || $written < 100) {
                @unlink($tempPath);
                return null;
            }

            $this->info('Downloaded offsite backup: ' . basename($newest) . ' (' . number_format($written) . ' bytes)');
            return $tempPath;
        } catch (\Throwable $e) {
            Log::warning('Offsite backup download failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function failVerify(string $message, array $output, int $exitCode, string $decryptedFile, string $sqlFile): void
    {
        $detail = implode("\n", $output) ?: 'no output';
        $this->error($message . ' (exit: ' . $exitCode . ')');
        $this->error($detail);
        Log::error('Backup verification failed.', [
            'reason' => $message,
            'exit_code' => $exitCode,
            'output' => $detail,
        ]);
        $this->logAudit('backup_verify_failed', 'Backup verification failed: ' . $message);

        @unlink($decryptedFile);
        @unlink($sqlFile);
        @unlink($sqlFile . '.gzerr');
    }
}
