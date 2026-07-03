<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalApplications = Application::count();
        $pendingApplications = Application::whereIn('status', ['submitted', 'screening'])->count();
        $approvedThisMonth = Application::whereIn('status', [
            'claimed', 'cheque_ready', 'budget_checking', 'with_treasurer',
            'voucher_checking', 'voucher_creation', 'assistance_coding',
            'social_case_study_uploaded', 'mswdo_review',
        ])->whereMonth('created_at', now()->month)->count();

        $recentActivity = AuditLog::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'module' => $log->module,
                'user_name' => $log->user?->full_name ?? 'System',
                'created_at' => $log->created_at,
            ]);

        return Inertia::render('Dashboard', [
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'approvedThisMonth' => $approvedThisMonth,
            'recentActivity' => $recentActivity,
        ]);
    }
}
