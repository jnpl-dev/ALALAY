<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyBackup extends Command
{
    protected $signature = 'backup:verify';

    protected $description = 'Restore the latest backup to a test database to verify integrity';

    public function handle(): void
    {
        $backupPath = config('backup.path');
        $encryptPass = config('backup.encrypt_pass');
        $testDatabase = config('backup.test_database');

        if (!$encryptPass) {
            $this->error('BACKUP_ENCRYPT_PASS is not set in config/backup.php');
            return;
        }

        $files = glob($backupPath . '/alalay_*.sql.gz.enc');

        if (empty($files)) {
            $this->warn('No backup files found in ' . $backupPath);
            Log::warning('Backup verification failed: no backup file found.');
            return;
        }

        $latest = collect($files)->sortByDesc(fn ($f) => filemtime($f))->first();

        $this->info('Verifying: ' . basename($latest));

        $result = shell_exec(
            'openssl enc -d -aes-256-cbc -pbkdf2 -pass pass:' . escapeshellarg($encryptPass) .
            ' -in ' . escapeshellarg($latest) .
            ' | gunzip | mysql -u root ' . escapeshellarg($testDatabase) . ' 2>&1'
        );

        if ($result === null) {
            $this->info('Backup verified successfully: ' . basename($latest));
            Log::info('Backup verification passed.', ['file' => basename($latest)]);
        } else {
            $this->error('Verification failed: ' . $result);
            Log::error('Backup verification failed.', ['file' => basename($latest), 'error' => $result]);
        }
    }
}
