<?php

namespace App\Http\Controllers\MayorsOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());

        $totalApplications = Application::count();
        $approvedThisMonth = Application::whereIn('status', ['claimed', 'cheque_ready'])
            ->whereMonth('created_at', now()->month)
            ->count();
        $totalDisbursed = Application::where('status', 'claimed')->sum('amount_granted');
        $beneficiariesServed = Application::whereIn('status', ['claimed', 'cheque_ready'])
            ->distinct('claimant_email')
            ->count('claimant_email');

        $monthlyTrends = Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count, sum(amount_granted) as total")
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
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

        return Inertia::render('MayorsOffice/Analytics', [
            'totalApplications' => $totalApplications,
            'approvedThisMonth' => $approvedThisMonth,
            'totalDisbursed' => $totalDisbursed,
            'beneficiariesServed' => $beneficiariesServed,
            'monthlyTrends' => $monthlyTrends,
            'dateFrom' => $from,
            'dateTo' => $to,
            'applicationsByCategory' => $applicationsByCategory,
        ]);
    }
}
