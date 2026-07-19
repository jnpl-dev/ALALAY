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

        $data = Cache::remember($cacheKey, 900, function () {
            $totalUsers = User::count();
            $activeUsers = User::active()->count();
            $inactiveUsers = User::where('status', '!=', 'active')->count();
            $totalApplications = Application::count();

            $applicationsByStatus = Application::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $recentActivity = AuditLog::with('user')
                ->latest()
                ->take(10)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'module' => $log->module,
                    'user_name' => $log->user?->full_name ?? 'System',
                    'created_at' => $log->created_at,
                ]);

            return compact('totalUsers', 'activeUsers', 'inactiveUsers', 'totalApplications', 'applicationsByStatus', 'recentActivity');
        });

        return Inertia::render('Admin/Analytics', array_merge($data, [
            'dateFrom' => $from,
            'dateTo' => $to,
        ]));
    }
}
