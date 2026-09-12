<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;

abstract class Controller
{
    use AuthorizesRequests;

    protected function fillWeekDates(Carbon $weekStart, $counts): array
    {
        $allDates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i)->format('Y-m-d');
            $allDates[$date] = 0;
        }
        foreach ($counts as $row) {
            $allDates[$row->date] = (int) $row->count;
        }
        return collect($allDates)->map(fn ($count, $date) => [
            'date' => $date,
            'count' => $count,
        ])->values()->toArray();
    }
}
