<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Voucher;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        return Inertia::render('Admin/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($monthStart, $lastMonthEnd) {
                return [
                    'total_users' => User::count(),
                    'total_users_change' => User::count() - User::where('created_at', '<=', $lastMonthEnd)->count(),
                    'active_users' => User::active()->count(),
                    'active_users_change' => User::active()->count() - User::active()
                        ->where('created_at', '<=', $lastMonthEnd)->count(),
                    'inactive_users' => User::where('status', '!=', 'active')->count(),
                    'inactive_users_change' => User::where('status', '!=', 'active')->count() - User::where('status', '!=', 'active')
                        ->where('created_at', '<=', $lastMonthEnd)->count(),
                    'users_by_role' => User::selectRaw('role, count(*) as count')
                        ->groupBy('role')->get(),
                    'recent_activity' => AuditLog::with('user')
                        ->latest()->take(5)->get()
                        ->map(fn ($log) => [
                            'id' => $log->id,
                            'module' => $log->module,
                            'action' => $log->action,
                            'user_name' => $log->user?->full_name ?? 'System',
                            'created_at' => $log->created_at,
                        ]),
                    'unusual_activity' => AuditLog::whereIn('action', ['login_lockout', 'login_failed'])
                        ->orWhere('module', 'system')
                        ->latest()->take(5)->get()
                        ->map(fn ($log) => [
                            'id' => $log->id,
                            'module' => $log->module,
                            'action' => $log->action,
                            'description' => $log->description,
                            'user_name' => $log->user?->full_name ?? 'System',
                            'created_at' => $log->created_at,
                        ]),
                ];
            }),
        ]);
    }
}
