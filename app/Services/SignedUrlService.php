<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SignedUrlService
{
    public function generate(string $path, int $expiresInMinutes = 15): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            return Storage::disk('supabase')->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
        } catch (\Exception $e) {
            return null;
        }
    }
}
