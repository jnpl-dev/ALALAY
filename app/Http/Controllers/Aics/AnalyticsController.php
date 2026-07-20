<?php

namespace App\Http\Controllers\Aics;

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
        $cacheKey = 'analytics.aics.' . md5("{$from}-{$to}");

        return Inertia::render('Aics/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'totalApplications' => Cache::remember("{$cacheKey}.total", 900, fn () => Application::count()),
                'pendingApplications' => Cache::remember("{$cacheKey}.pending", 900, fn () => Application::whereIn('status', ['submitted', 'screening'])->count()),
                'forwardedApplications' => Cache::remember("{$cacheKey}.forwarded", 900, fn () => Application::where('status', 'mswdo_review')->count()),
                'returnedApplications' => Cache::remember("{$cacheKey}.returned", 900, fn () => Application::where('status', 'returned_to_applicant')->count()),
                'applicationsByStatus' => Cache::remember("{$cacheKey}.status", 900, fn () => Application::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')),
                'monthlyTrends' => Cache::remember("{$cacheKey}.trends", 900, fn () => Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")->whereBetween('created_at', [$from, $to . ' 23:59:59'])->groupBy('month')->orderBy('month')->pluck('count', 'month')),
                'recentApplications' => Cache::remember("{$cacheKey}.recent", 900, fn () => Application::with('category')->latest()->take(10)->get()->map(fn ($app) => [
                    'id' => $app->id,
                    'reference_code' => $app->reference_code,
                    'status' => $app->status,
                    'category_name' => $app->category?->category_name,
                    'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                    'created_at' => $app->created_at,
                ])),
            ]),
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }
}
