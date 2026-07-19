<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $cacheKey = 'dashboard.accountant.' . now()->format('YmdHi');
        $data = Cache::remember($cacheKey, 300, function () {
            $totalVouchers = Application::whereIn('status', ['voucher_creation', 'voucher_checking'])->count();
            $pendingApproval = Application::where('status', 'voucher_checking')->count();
            $approvedThisMonth = Application::whereIn('status', ['budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])->whereMonth('created_at', now()->month)->count();

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

            return compact('totalVouchers', 'pendingApproval', 'approvedThisMonth', 'recentActivity');
        });

        return Inertia::render('Dashboard', [
            'totalApplications' => $data['totalVouchers'],
            'pendingApplications' => $data['pendingApproval'],
            'approvedThisMonth' => $data['approvedThisMonth'],
            'recentActivity' => $data['recentActivity'],
        ]);
    }
}
