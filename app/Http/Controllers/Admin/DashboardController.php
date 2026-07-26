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
        return Inertia::render('Admin/Dashboard', [
            'dashboardData' => Inertia::defer(fn () => [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'inactive_users' => User::where('status', '!=', 'active')->count(),
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
            ]),
        ]);
    }
}
