<?php

namespace App\Console\Commands\Traits;

trait ResolvesBinaries
{
    /**
     * Resolve a binary to an absolute path so commands work even when the
     * directory is not on PATH (e.g. local XAMPP) and in production images
     * where apt installs to /usr/bin.
     *
     * Order: PATH scan → common install locations → bare name fallback.
     * Always returns forward slashes (valid for exec on Windows and in bash).
     */
    protected function resolveBinary(string $name): string
    {
        $suffix = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';

        $path = getenv('PATH') ?: '';
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            if ($dir === '') {
                continue;
            }
            $candidate = rtrim($dir, "/\\") . '/' . $name . $suffix;
            if (is_file($candidate)) {
                return str_replace('\\', '/', $candidate);
            }
        }

        $fallbackDirs = [
            'C:/xampp1/mysql/bin',
            'C:/xampp/mysql/bin',
            'C:/xampp-portable/mysql/bin',
            '/usr/bin',
            '/usr/local/bin',
            '/usr/local/mysql/bin',
            '/opt/homebrew/bin',
        ];
        foreach ($fallbackDirs as $dir) {
            $candidate = $dir . '/' . $name . $suffix;
            if (is_file($candidate)) {
                return str_replace('\\', '/', $candidate);
            }
        }

        return $name;
    }
}
