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

        $data = Cache::remember($cacheKey, 900, function () use ($from, $to) {
            $chequesForProcessing = Application::where('status', 'with_treasurer')->count();
            $acknowledgedThisMonth = Application::whereIn('status', ['cheque_ready', 'claimed'])
                ->whereMonth('created_at', now()->month)
                ->count();
            $totalAmount = Application::whereIn('applications.status', ['with_treasurer', 'cheque_ready', 'claimed'])
                ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                ->sum('assistance_codes.amount');
            $pendingCheques = Application::where('status', 'with_treasurer')->count();

            $monthlyTrends = Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")
                ->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])
                ->whereIn('applications.status', ['with_treasurer', 'cheque_ready', 'claimed'])
                ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $recentCheques = Application::with('category', 'encoder', 'assistanceCode')
                ->whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])
                ->latest()
                ->take(10)
                ->get()
                ->map(fn ($app) => [
                    'id' => $app->id,
                    'reference_code' => $app->reference_code,
                    'status' => $app->status,
                    'category_name' => $app->category?->category_name,
                    'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                    'amount' => $app->assistanceCode?->amount,
                    'created_at' => $app->created_at,
                ]);

            return compact('chequesForProcessing', 'acknowledgedThisMonth', 'totalAmount', 'pendingCheques', 'monthlyTrends', 'recentCheques');
        });

        return Inertia::render('Treasurer/Analytics', array_merge($data, [
            'dateFrom' => $from,
            'dateTo' => $to,
        ]));
    }
}
