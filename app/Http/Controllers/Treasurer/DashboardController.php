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
        $cacheKey = 'dashboard.treasurer.' . now()->format('YmdHi');
        $data = Cache::remember($cacheKey, 300, function () {
            $totalCheques = Application::whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])->count();
            $pendingProcessing = Application::where('status', 'with_treasurer')->count();
            $acknowledgedThisMonth = Application::where('status', 'cheque_ready')
                ->whereMonth('created_at', now()->month)
                ->count();

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

            return compact('totalCheques', 'pendingProcessing', 'acknowledgedThisMonth', 'recentActivity');
        });

        return Inertia::render('Dashboard', [
            'totalApplications' => $data['totalCheques'],
            'pendingApplications' => $data['pendingProcessing'],
            'approvedThisMonth' => $data['acknowledgedThisMonth'],
            'recentActivity' => $data['recentActivity'],
        ]);
    }
}
