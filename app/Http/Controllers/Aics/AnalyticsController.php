<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());

        $totalApplications = Application::count();
        $pendingApplications = Application::whereIn('status', ['submitted', 'screening', 'mswdo_review'])->count();
        $approvedThisMonth = Application::whereIn('status', [
            'claimed', 'cheque_ready', 'budget_checking', 'with_treasurer',
            'voucher_checking', 'voucher_creation', 'assistance_coding',
            'social_case_study_uploaded', 'mswdo_review',
        ])->whereMonth('created_at', now()->month)->count();
        $codesIssued = Application::where('status', '!=', 'submitted')->where('status', '!=', 'screening')->count();

        $applicationsByStatus = Application::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $monthlyTrends = Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $recentApplications = Application::with('category', 'encoder')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'reference_code' => $app->reference_code,
                'status' => $app->status,
                'category_name' => $app->category?->name,
                'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                'created_at' => $app->created_at,
            ]);

        return Inertia::render('Aics/Analytics', [
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'approvedThisMonth' => $approvedThisMonth,
            'codesIssued' => $codesIssued,
            'applicationsByStatus' => $applicationsByStatus,
            'monthlyTrends' => $monthlyTrends,
            'dateFrom' => $from,
            'dateTo' => $to,
            'recentApplications' => $recentApplications,
        ]);
    }
}
