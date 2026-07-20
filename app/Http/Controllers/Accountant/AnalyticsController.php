<?php

namespace App\Http\Controllers\Accountant;

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
        $cacheKey = 'analytics.accountant.' . md5("{$from}-{$to}");

        return Inertia::render('Accountant/Analytics', [
            'analyticsData' => Inertia::defer(fn () => [
                'vouchersForReview' => Cache::remember("{$cacheKey}.vouchers", 900, fn () => Application::whereIn('status', ['voucher_creation', 'voucher_checking'])->count()),
                'approvedThisMonth' => Cache::remember("{$cacheKey}.approved", 900, fn () => Application::whereIn('status', ['budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count()),
                'totalAmount' => Cache::remember("{$cacheKey}.totalAmount", 900, fn () => Application::whereIn('applications.status', ['voucher_creation', 'voucher_checking', 'budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->sum('assistance_codes.amount')),
                'disbursedThisMonth' => Cache::remember("{$cacheKey}.disbursed", 900, fn () => Application::where('applications.status', 'claimed')->whereBetween('applications.created_at', [now()->startOfMonth(), now()->endOfMonth()])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->sum('assistance_codes.amount')),
                'monthlyTrends' => Cache::remember("{$cacheKey}.trends", 900, fn () => Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])->whereIn('applications.status', ['voucher_creation', 'voucher_checking', 'budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')->groupBy('month')->orderBy('month')->get()),
                'recentTransactions' => Cache::remember("{$cacheKey}.recent", 900, fn () => Application::with('category', 'encoder', 'assistanceCode')->whereIn('status', ['voucher_creation', 'voucher_checking', 'budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])->latest()->take(10)->get()->map(fn ($app) => [
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
