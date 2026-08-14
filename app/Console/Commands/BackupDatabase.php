<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create encrypted database backup and upload to Supabase';

    public function handle(): void
    {
        $backupPath = config('backup.path');
        $encryptPass = config('backup.encrypt_pass');

        if (!$encryptPass) {
            $this->error('BACKUP_ENCRYPT_PASS is not set');
            return;
        }

        $db = config('database.connections.mysql');
        $filename = 'alalay_' . now()->format('Y-m-d_H-i-s') . '.sql.gz.enc';
        $filepath = rtrim($backupPath, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $this->info('Dumping database...');

        $command = sprintf(
            'mysqldump --single-transaction --routines --triggers --events -u %s %s %s',
            escapeshellarg($db['username']),
            $db['password'] ? '-p' . escapeshellarg($db['password']) : '',
            escapeshellarg($db['database'])
        );

        $fullCommand = $command
            . ' | gzip'
            . ' | openssl enc -aes-256-cbc -pbkdf2 -pass pass:' . escapeshellarg($encryptPass)
            . ' > ' . escapeshellarg($filepath);

        $output = null;
        $exitCode = null;
        exec($fullCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Backup failed (exit code: ' . $exitCode . ')');
            Log::error('Database backup failed.', ['exit_code' => $exitCode]);
            return;
        }

        $this->info('Local backup saved: ' . $filename);

        // Upload to Supabase
        $this->uploadToSupabase($filepath, $filename);

        // Prune old backups
        $this->pruneOldBackups($backupPath);

        Log::info('Database backup completed.', ['file' => $filename]);
        $this->info('Backup complete.');
    }

    protected function uploadToSupabase(string $filepath, string $filename): void
    {
        $bucket = config('backup.supabase_bucket');
        $endpoint = env('SUPABASE_STORAGE_ENDPOINT');
        $key = env('SUPABASE_KEY');
        $secret = env('SUPABASE_SECRET');

        if (!$endpoint || !$key || !$secret) {
            $this->warn('Supabase credentials not configured — skipping offsite upload.');
            return;
        }

        $this->info('Uploading to Supabase...');

        $resource = '/' . $bucket . '/db/' . $filename;
        $s3Endpoint = rtrim($endpoint, '/') . '/storage/v1/s3';
        $contentType = 'application/octet-stream';
        $date = gmdate('D, d M Y H:i:s T');
        $stringToSign = "PUT\n\n{$contentType}\n{$date}\n{$resource}";
        $signature = base64_encode(
            hash_hmac('sha256', $stringToSign, $secret, true)
        );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $s3Endpoint . $resource,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => fopen($filepath, 'r'),
            CURLOPT_INFILESIZE => filesize($filepath),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Host: ' . parse_url($s3Endpoint, PHP_URL_HOST),
                'Date: ' . $date,
                'Content-Type: ' . $contentType,
                'Authorization: AWS ' . $key . ':' . $signature,
            ],
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $this->info('Offsite upload complete.');
        } else {
            $this->warn('Upload returned HTTP ' . $httpCode . ' — check Supabase bucket exists.');
        }
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
}
