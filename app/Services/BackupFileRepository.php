<?php

namespace App\Services;

use App\Console\Commands\Traits\InteractsWithMysql;
use App\Console\Commands\Traits\ResolvesBinaries;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Single source of truth for backup file operations: listing (local +
 * Supabase merged), locating, deleting and snapshot creation.
 */
class BackupFileRepository
{
    use InteractsWithMysql, ResolvesBinaries;

    /** Strict filename allowlist — no path traversal possible. */
    public const FILENAME_PATTERN = '/\Aalalay_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz\.enc\z/';

    public function isValidFilename(string $file): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $file);
    }

    /**
     * Merge local + offsite listings into rows: {file, size, created_at, location}.
     * Offsite listing is a single ListObjectsV2 call via Flysystem's
     * listContents (path + size + lastModified). Network failure degrades
     * to local-only.
     *
     * @return array{items: array<int, array{file: string, size: int, created_at: int, location: string}>, offsite_available: bool}
     */
    public function listAll(): array
    {
        $rows = [];

        foreach (glob(rtrim(config('backup.path'), '/\\') . '/alalay_*.sql.gz.enc') ?: [] as $localPath) {
            if (!$this->isValidFilename(basename($localPath))) {
                continue;
            }
            $rows[basename($localPath)] = [
                'file' => basename($localPath),
                'size' => (int) filesize($localPath),
                'created_at' => (int) filemtime($localPath),
                'location' => 'local',
            ];
        }

        $offsiteAvailable = true;
        try {
            $driver = Storage::disk('supabase-backups')->getDriver();
            foreach ($driver->listContents('db', false) as $attrs) {
                if (!$attrs->isFile()) {
                    continue;
                }
                $name = basename($attrs->path());
                if (!$this->isValidFilename($name)) {
                    continue;
                }
                if (isset($rows[$name])) {
                    $rows[$name]['location'] = 'both';
                    continue;
                }
                $rows[$name] = [
                    'file' => $name,
                    'size' => (int) ($attrs->fileSize() ?? 0),
                    'created_at' => (int) ($attrs->lastModified() ?? 0),
                    'location' => 'offsite',
                ];
            }
        } catch (\Throwable $e) {
            $offsiteAvailable = false;
        }

        return ['items' => array_values($rows), 'offsite_available' => $offsiteAvailable];
    }

    /**
     * @param  array<int, array{file: string, size: int, created_at: int, location: string}>  $items
     * @return array<int, array{file: string, size: int, created_at: int, location: string}>
     */
    public function filter(array $items, ?string $search, ?string $from, ?string $to, ?string $location = null): array
    {
        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $items = array_values(array_filter(
                $items,
                fn (array $row): bool => str_contains(mb_strtolower($row['file']), $needle)
            ));
        }

        if ($from !== null && $from !== '') {
            $items = array_values(array_filter($items, fn (array $row): bool => date('Y-m-d', $row['created_at']) >= $from));
        }

        if ($to !== null && $to !== '') {
            $items = array_values(array_filter($items, fn (array $row): bool => date('Y-m-d', $row['created_at']) <= $to));
        }

        if ($location !== null && $location !== '' && in_array($location, ['local', 'offsite', 'both'], true)) {
            $items = array_values(array_filter($items, fn (array $row): bool => $row['location'] === $location));
        }

        return $items;
    }

    /**
     * Same JSON shape as AuditLogs' paginator (20/page).
     */
    public function paginate(?string $search, ?string $from, ?string $to, ?int $page = null, ?string $location = null): LengthAwarePaginator
    {
        $all = $this->listAll()['items'];
        $filtered = $this->filter($all, $search, $from, $to, $location);

        usort($filtered, fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        $perPage = 20;
        $page = max(1, $page ?? Paginator::resolveCurrentPage());
        $total = count($filtered);
        $slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        // Unix seconds → 'Y-m-d H:i:s' (same shape AuditLogs sends) so
        // dayjs() on the client doesn't read seconds as milliseconds (1970).
        $slice = array_map(function (array $row): array {
            $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            return $row;
        }, $slice);

        return new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Return a local filesystem path for the given backup file: the local
     * copy if present, otherwise downloaded from Supabase into the backup
     * directory (original filename so retention pruning manages it).
     * Returns null when the file exists in neither location.
     */
    public function locate(string $file): ?string
    {
        if (!$this->isValidFilename($file)) {
            return null;
        }

        $localPath = rtrim(config('backup.path'), '/\\') . DIRECTORY_SEPARATOR . $file;
        if (is_file($localPath)) {
            return $localPath;
        }

        try {
            $disk = Storage::disk('supabase-backups');
            $remote = 'db/' . $file;
            if (!$disk->exists($remote)) {
                return null;
            }
            $stream = $disk->readStream($remote);
            if (!$stream) {
                return null;
            }
            if (!is_dir(dirname($localPath))) {
                mkdir(dirname($localPath), 0755, true);
            }
            $written = file_put_contents($localPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($written === false || $written < 100) {
                @unlink($localPath);
                return null;
            }

            return $localPath;
        } catch (\Throwable $e) {
            @unlink($localPath);
            return null;
        }
    }

    /**
     * Delete from both locations. Returns which locations actually had the file.
     *
     * @return array{local: bool, offsite: bool}
     */
    public function delete(string $file): array
    {
        if (!$this->isValidFilename($file)) {
            return ['local' => false, 'offsite' => false];
        }

        $removed = ['local' => false, 'offsite' => false];

        $localPath = rtrim(config('backup.path'), '/\\') . DIRECTORY_SEPARATOR . $file;
        if (is_file($localPath)) {
            $removed['local'] = @unlink($localPath);
        }

        try {
            $disk = Storage::disk('supabase-backups');
            if ($disk->exists('db/' . $file)) {
                $removed['offsite'] = (bool) $disk->delete('db/' . $file);
            }
        } catch (\Throwable $e) {
            // offsite delete failure leaves the row visible; reported by caller
        }

        return $removed;
    }

    /**
     * Dump → gzip → encrypt → save locally → (optionally) upload offsite.
     * Shared by `backup:run` and the pre-restore snapshot.
     *
     * The local write is an unavoidable intermediate of the shell pipeline:
     * - destination 'local'  → upload skipped, local copy kept;
     * - destination 'offsite'→ local copy removed after a *successful* upload
     *   (kept on upload failure so nothing is ever lost);
     * - destination 'both'   → local kept + uploaded.
     *
     * Policy is the caller's: dump failure throws (backup:run fails,
     * restore aborts); `uploaded` false is a soft signal (backup:run fails,
     * restore warns and proceeds).
     *
     * @return array{file: string, path: string, size: int, uploaded: bool, upload_error: ?string}
     */
    public function snapshot(string $destination = 'both'): array
    {
        if (!in_array($destination, ['local', 'offsite', 'both'], true)) {
            throw new InvalidArgumentException("Invalid backup destination \"{$destination}\" — expected local, offsite, or both.");
        }

        $encryptPass = config('backup.encrypt_pass');
        if (!$encryptPass) {
            throw new RuntimeException('BACKUP_ENCRYPT_PASS is not set.');
        }

        $db = config('database.connections.mysql');
        $backupPath = rtrim(config('backup.path'), '/\\');
        $filename = 'alalay_' . now()->format('Y-m-d_H-i-s') . '.sql.gz.enc';
        $filepath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $dumpCmd = sprintf(
            '%s --single-transaction --routines --triggers --events -h %s -P %s -u %s %s',
            escapeshellarg($this->resolveBinary('mysqldump')),
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            escapeshellarg($db['database'])
        );

        $bashFilepath = str_replace('\\', '/', $filepath);
        $bashPass = str_replace("'", "'\\''", $encryptPass);

        $innerCmd = 'set -o pipefail; ' . $dumpCmd
            . ' | gzip'
            . " | openssl enc -aes-256-cbc -pbkdf2 -pass pass:'{$bashPass}'"
            . " > '{$bashFilepath}'";

        $output = [];
        $exitCode = 0;
        $this->withMysqlPassword((string) ($db['password'] ?? ''), function () use ($innerCmd, &$output, &$exitCode) {
            exec('bash -c ' . escapeshellarg($innerCmd), $output, $exitCode);
        });

        if ($exitCode !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
            $size = file_exists($filepath) ? filesize($filepath) : 0;
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            throw new RuntimeException("mysqldump/gzip/openssl pipeline failed (exit code {$exitCode}, wrote {$size} bytes).");
        }

        $uploaded = false;
        $uploadError = null;
        if ($destination !== 'local' && config('backup.supabase_key') && config('backup.supabase_secret')) {
            try {
                $stream = fopen($filepath, 'r');
                if ($stream) {
                    $uploaded = (bool) Storage::disk('supabase-backups')->put('db/' . $filename, $stream);
                    fclose($stream);
                }
                if (!$uploaded) {
                    $uploadError = 'Storage::put returned false.';
                }
            } catch (\Throwable $e) {
                $uploaded = false;
                $uploadError = $e->getMessage();
            }
        }

        $size = (int) filesize($filepath);

        // Offsite-only: the local dump was only a staging intermediate —
        // remove it once (and only once) the offsite copy is confirmed.
        if ($destination === 'offsite' && $uploaded && file_exists($filepath)) {
            @unlink($filepath);
        }

        return [
            'file' => $filename,
            'path' => $filepath,
            'size' => $size,
            'uploaded' => $uploaded,
            'upload_error' => $uploadError,
        ];
    }
}
