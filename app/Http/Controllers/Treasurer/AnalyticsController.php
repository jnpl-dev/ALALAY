<?php

namespace App\Http\Controllers\Treasurer;

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
        $cacheKey = 'analytics.treasurer.' . md5("{$from}-{$to}");

        return Inertia::render('Treasurer/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'chequesForProcessing' => Cache::remember("{$cacheKey}.cheques", 900, fn () => Application::where('status', 'with_treasurer')->count()),
                'acknowledgedThisMonth' => Cache::remember("{$cacheKey}.acknowledged", 900, fn () => Application::whereIn('status', ['cheque_ready', 'claimed'])->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count()),
                'totalAmount' => Cache::remember("{$cacheKey}.totalAmount", 900, fn () => Application::whereIn('applications.status', ['with_treasurer', 'cheque_ready', 'claimed'])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->sum('assistance_codes.amount')),
                'pendingCheques' => Cache::remember("{$cacheKey}.pending", 900, fn () => Application::where('status', 'with_treasurer')->count()),
                'monthlyTrends' => Cache::remember("{$cacheKey}.trends", 900, fn () => Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])->whereIn('applications.status', ['with_treasurer', 'cheque_ready', 'claimed'])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->groupBy('month')->orderBy('month')->get()),
                'recentCheques' => Cache::remember("{$cacheKey}.recent", 900, fn () => Application::with('category', 'encoder', 'assistanceCode')->whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])->latest()->take(10)->get()->map(fn ($app) => [
                    'id' => $app->id,
                    'reference_code' => $app->reference_code,
                    'status' => $app->status,
                    'category_name' => $app->category?->category_name,
                    'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                    'amount' => $app->assistanceCode?->amount,
                    'created_at' => $app->created_at,
                ])),
            ]),
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }
}
