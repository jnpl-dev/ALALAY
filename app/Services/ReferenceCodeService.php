<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Str;

class ReferenceCodeService
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $random = strtoupper(Str::random(6));

        $code = "GMN-{$year}-{$random}";

        while (Application::where('reference_code', $code)->exists()) {
            $random = strtoupper(Str::random(6));
            $code = "GMN-{$year}-{$random}";
        }

        return $code;
    }
}
