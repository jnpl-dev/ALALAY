<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'dashboardData' => Inertia::defer(fn () => Cache::remember(
                'dashboard.treasurer.' . now()->format('YmdH'), 300,
                fn () => [
                    'totalApplications' => Application::whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])->count(),
                    'pendingApplications' => Application::where('status', 'with_treasurer')->count(),
                    'approvedThisMonth' => Application::where('status', 'cheque_ready')
                        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                    'recentActivity' => AuditLog::with('user')
                        ->latest()->take(5)->get()
                        ->map(fn ($log) => [
                            'id' => $log->id,
                            'action' => $log->action,
                            'module' => $log->module,
                            'user_name' => $log->user?->full_name ?? 'System',
                            'created_at' => $log->created_at,
                        ]),
                ]
            )),
        ]);
    }
}
