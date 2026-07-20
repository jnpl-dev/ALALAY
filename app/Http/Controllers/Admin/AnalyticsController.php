<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());
        $cacheKey = 'analytics.admin.' . md5("{$from}-{$to}");

        return Inertia::render('Admin/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'totalUsers' => Cache::remember("{$cacheKey}.totalUsers", 900, fn () => User::count()),
                'activeUsers' => Cache::remember("{$cacheKey}.activeUsers", 900, fn () => User::active()->count()),
                'inactiveUsers' => Cache::remember("{$cacheKey}.inactiveUsers", 900, fn () => User::where('status', '!=', 'active')->count()),
                'totalApplications' => Cache::remember("{$cacheKey}.totalApplications", 900, fn () => Application::count()),
                'applicationsByStatus' => Cache::remember("{$cacheKey}.status", 900, fn () => Application::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')),
                'recentActivity' => Cache::remember("{$cacheKey}.activity", 900, fn () => AuditLog::with('user')->latest()->take(10)->get()->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'module' => $log->module,
                    'user_name' => $log->user?->full_name ?? 'System',
                    'created_at' => $log->created_at,
                ])),
            ]),
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }
}
