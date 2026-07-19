<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());
        $cacheKey = 'analytics.mswdo.' . md5("{$from}-{$to}");

        $data = Cache::remember($cacheKey, 900, function () use ($from, $to) {
            $forValidation = Application::whereIn('status', ['submitted', 'screening', 'mswdo_review'])->count();
            $validatedThisMonth = Application::whereIn('status', [
                'claimed', 'cheque_ready', 'budget_checking', 'with_treasurer',
                'voucher_checking', 'voucher_creation', 'assistance_coding',
                'social_case_study_uploaded',
            ])->whereMonth('created_at', now()->month)->count();
            $returned = Application::where('status', 'returned_to_applicant')->count();
            $vouchersPrepared = Application::where('status', 'voucher_creation')->count();

            $applicationsByStatus = Application::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $monthlyTrends = Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month');

            $pendingActions = AuditLog::with('user')
                ->whereIn('module', ['application', 'review'])
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

            return compact('forValidation', 'validatedThisMonth', 'returned', 'vouchersPrepared', 'applicationsByStatus', 'monthlyTrends', 'pendingActions');
        });

        return Inertia::render('Mswdo/Analytics', array_merge($data, [
            'dateFrom' => $from,
            'dateTo' => $to,
        ]));
    }
}
