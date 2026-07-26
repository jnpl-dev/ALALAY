<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait HasPollCache
{
    public function poll(Request $request): JsonResponse
    {
        $since = $request->query('since');

        if ($since === null) {
            $data = $this->getPollData($request);

            return response()->json([
                'changed' => true,
                'data' => $data,
                'last_checked' => now()->toIso8601String(),
            ]);
        }

        $cacheKey = 'poll:' . static::class;
        $lastChange = Cache::get($cacheKey);

        if ($lastChange !== null && $lastChange->lte($since)) {
            return response()->json([
                'changed' => false,
                'data' => [],
                'last_checked' => now()->toIso8601String(),
            ]);
        }

        $data = $this->getPollData($request);

        return response()->json([
            'changed' => true,
            'data' => $data,
            'last_checked' => now()->toIso8601String(),
        ]);
    }

    protected function bustPollCache(): void
    {
        $cacheKey = 'poll:' . static::class;
        Cache::put($cacheKey, now(), now()->addDay());
    }

    abstract protected function getPollData(Request $request): array;
}
