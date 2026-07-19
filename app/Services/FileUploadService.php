<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FileUploadService
{
    public function upload(
        UploadedFile $file,
        string $table,
        string $entityId,
        ?int $maxSizeKb = null,
        ?array $allowedMimes = null,
    ): array {
        $maxSizeKb = $maxSizeKb ?? (int) Cache::remember('settings.max_file_size_kb', 1800, fn () =>
            \App\Models\SystemSetting::byKey('max_file_size_kb')->first()?->setting_value ?? 5120
        );
        $allowedMimes = $allowedMimes ?? explode(',', Cache::remember('settings.allowed_mime_types', 1800, fn () =>
            \App\Models\SystemSetting::byKey('allowed_mime_types')->first()?->setting_value ?? 'image/jpeg,image/png,application/pdf'
        ));

        if ($file->getSize() > $maxSizeKb * 1024) {
            throw new HttpException(413, 'File exceeds maximum size.');
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            Log::warning('FileUploadService: rejected file type', [
                'mime' => $file->getMimeType(),
                'allowed' => $allowedMimes,
                'original_name' => $file->getClientOriginalName(),
            ]);
            throw new HttpException(415, 'File type not allowed. Allowed: ' . implode(', ', $allowedMimes));
        }

        $path = "{$table}/{$entityId}/" . $file->hashName();

        Storage::disk('supabase')->put($path, file_get_contents($file->getRealPath()));

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }
}
