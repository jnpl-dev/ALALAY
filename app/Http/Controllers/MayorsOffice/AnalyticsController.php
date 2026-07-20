<?php

namespace App\Http\Controllers\MayorsOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());
        $cacheKey = 'analytics.mayors-office.' . md5("{$from}-{$to}");

        return Inertia::render('MayorsOffice/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'totalApplications' => Cache::remember("{$cacheKey}.total", 900, fn () => Application::count()),
                'approvedThisMonth' => Cache::remember("{$cacheKey}.approved", 900, fn () => Application::whereIn('status', ['claimed', 'cheque_ready'])->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count()),
                'totalDisbursed' => Cache::remember("{$cacheKey}.disbursed", 900, fn () => Application::where('applications.status', 'claimed')->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->sum('assistance_codes.amount')),
                'beneficiariesServed' => Cache::remember("{$cacheKey}.beneficiaries", 900, fn () => Application::whereIn('status', ['claimed', 'cheque_ready'])->distinct('claimant_email')->count('claimant_email')),
                'monthlyTrends' => Cache::remember("{$cacheKey}.trends", 900, fn () => Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->groupBy('month')->orderBy('month')->get()),
                'applicationsByCategory' => Cache::remember("{$cacheKey}.category", 900, fn () => Application::selectRaw('category_id, count(*) as count')->with('category')->groupBy('category_id')->get()->map(fn ($app) => [
                    'category_name' => $app->category?->category_name ?? 'Uncategorized',
                    'count' => $app->count,
                ])),
            ]),
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }
}
