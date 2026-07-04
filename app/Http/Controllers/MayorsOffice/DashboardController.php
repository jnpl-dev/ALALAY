<?php

namespace App\Http\Controllers\MayorsOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalApplications = Application::count();
        $approvedApplications = Application::whereIn('status', ['claimed', 'cheque_ready'])->count();
        $totalDisbursed = Application::where('status', 'claimed')->sum('amount_granted');

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
            'pendingApplications' => $approvedApplications,
            'approvedThisMonth' => $totalDisbursed,
            'recentActivity' => $recentActivity,
        ]);
    }
}
