<?php

namespace App\Http\Controllers\Mswdo;

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
                'dashboard.mswdo.' . now()->format('YmdH'), 300,
                fn () => [
                    'totalApplications' => Application::count(),
                    'pendingApplications' => Application::whereIn('status', ['submitted', 'screening', 'mswdo_review'])->count(),
                    'approvedThisMonth' => Application::whereIn('status', [
                        'claimed', 'cheque_ready', 'budget_checking', 'with_treasurer',
                        'voucher_checking', 'voucher_creation', 'assistance_coding',
                        'social_case_study_uploaded',
                    ])->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
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
