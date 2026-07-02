<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class FileUploadService
{
    public function upload(
        UploadedFile $file,
        string $table,
        string $entityId,
        ?int $maxSizeKb = null,
        ?array $allowedMimes = null,
    ): array {
        $maxSizeKb = $maxSizeKb ?? (int) (\App\Models\SystemSetting::byKey('max_file_size_kb')->first()?->setting_value ?? 5120);
        $allowedMimes = $allowedMimes ?? explode(',', \App\Models\SystemSetting::byKey('allowed_mime_types')->first()?->setting_value ?? 'image/jpeg,image/png');

        abort_if($file->getSize() > $maxSizeKb * 1024, 413, 'File exceeds maximum size.');
        abort_if(!in_array($file->getMimeType(), $allowedMimes), 415, 'File type not allowed.');

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
