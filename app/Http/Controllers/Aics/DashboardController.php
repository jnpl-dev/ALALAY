<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $cacheKey = 'dashboard.aics.' . now()->format('YmdHi');
        $data = Cache::remember($cacheKey, 300, function () {
            $totalApplications = Application::count();
            $pendingReview = Application::whereIn('status', ['submitted', 'screening'])->count();
            $forwarded = Application::where('status', 'mswdo_review')->count();
            $returned = Application::where('status', 'returned_to_applicant')->count();

            $recentApplications = Application::with('category')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($app) => [
                    'id' => $app->id,
                    'reference_code' => $app->reference_code,
                    'status' => $app->status,
                    'category_name' => $app->category?->category_name,
                    'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                    'created_at' => $app->created_at,
                ]);

            return compact('totalApplications', 'pendingReview', 'forwarded', 'returned', 'recentApplications');
        });

        return Inertia::render('Aics/Dashboard', [
            'totalApplications' => $data['totalApplications'],
            'pendingApplications' => $data['pendingReview'],
            'forwardedApplications' => $data['forwarded'],
            'returnedApplications' => $data['returned'],
            'recentApplications' => $data['recentApplications'],
        ]);
    }
}
