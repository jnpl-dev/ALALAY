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

        $data = Cache::remember($cacheKey, 900, function () use ($from, $to) {
            $totalApplications = Application::count();
            $approvedThisMonth = Application::whereIn('status', ['claimed', 'cheque_ready'])
                ->whereMonth('created_at', now()->month)
                ->count();
            $totalDisbursed = Application::where('applications.status', 'claimed')
                ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                ->sum('assistance_codes.amount');
            $beneficiariesServed = Application::whereIn('status', ['claimed', 'cheque_ready'])
                ->distinct('claimant_email')
                ->count('claimant_email');

            $monthlyTrends = Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")
                ->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])
                ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $applicationsByCategory = Application::selectRaw('category_id, count(*) as count')
                ->with('category')
                ->groupBy('category_id')
                ->get()
                ->map(fn ($app) => [
                    'category_name' => $app->category?->category_name ?? 'Uncategorized',
                    'count' => $app->count,
                ]);

            return compact('totalApplications', 'approvedThisMonth', 'totalDisbursed', 'beneficiariesServed', 'monthlyTrends', 'applicationsByCategory');
        });

        return Inertia::render('MayorsOffice/Analytics', array_merge($data, [
            'dateFrom' => $from,
            'dateTo' => $to,
        ]));
    }
}
