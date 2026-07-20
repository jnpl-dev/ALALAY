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

        return Inertia::render('Mswdo/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'forValidation' => Cache::remember("{$cacheKey}.forValidation", 900, fn () => Application::whereIn('status', ['submitted', 'screening', 'mswdo_review'])->count()),
                'validatedThisMonth' => Cache::remember("{$cacheKey}.validated", 900, fn () => Application::whereIn('status', ['claimed', 'cheque_ready', 'budget_checking', 'with_treasurer', 'voucher_checking', 'voucher_creation', 'assistance_coding', 'social_case_study_uploaded'])->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count()),
                'returned' => Cache::remember("{$cacheKey}.returned", 900, fn () => Application::where('status', 'returned_to_applicant')->count()),
                'vouchersPrepared' => Cache::remember("{$cacheKey}.vouchers", 900, fn () => Application::where('status', 'voucher_creation')->count()),
                'monthlyTrends' => Cache::remember("{$cacheKey}.trends", 900, fn () => Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")->whereBetween('created_at', [$from, $to . ' 23:59:59'])->groupBy('month')->orderBy('month')->pluck('count', 'month')),
                'pendingActions' => Cache::remember("{$cacheKey}.actions", 900, fn () => AuditLog::with('user')->whereIn('module', ['application', 'review'])->latest()->take(10)->get()->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'module' => $log->module,
                    'user_name' => $log->user?->full_name ?? 'System',
                    'created_at' => $log->created_at,
                ])),
            ]),
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }
}
