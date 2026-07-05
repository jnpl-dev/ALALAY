<?php

namespace App\Http\Controllers\Aics;

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
        $pendingReview = Application::whereIn('status', ['submitted', 'screening'])->count();
        $forwarded = Application::where('status', 'mswdo_review')->count();
        $returned = Application::where('status', 'returned_to_applicant')->count();

        $applicationsByStatus = Application::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $monthlyTrends = Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $recentApplications = Application::with('category')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'reference_code' => $app->reference_code,
                'status' => $app->status,
                'category_name' => $app->category?->category_name,
                'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                'created_at' => $app->created_at,
            ]);

        return Inertia::render('Aics/Analytics', [
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingReview,
            'forwardedApplications' => $forwarded,
            'returnedApplications' => $returned,
            'applicationsByStatus' => $applicationsByStatus,
            'monthlyTrends' => $monthlyTrends,
            'dateFrom' => $from,
            'dateTo' => $to,
            'recentApplications' => $recentApplications,
        ]);
    }
}
