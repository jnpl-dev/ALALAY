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
        return Inertia::render('Aics/Dashboard', [
            'dashboardData' => Inertia::defer(fn () => Cache::remember(
                'dashboard.aics.' . now()->format('YmdH'), 300,
                fn () => [
                    'totalApplications' => Application::count(),
                    'pendingApplications' => Application::whereIn('status', ['submitted', 'screening'])->count(),
                    'forwardedApplications' => Application::where('status', 'mswdo_review')->count(),
                    'returnedApplications' => Application::where('status', 'returned_to_applicant')->count(),
                    'recentApplications' => Application::with('category')
                        ->latest()->take(5)->get()
                        ->map(fn ($app) => [
                            'id' => $app->id,
                            'reference_code' => $app->reference_code,
                            'status' => $app->status,
                            'category_name' => $app->category?->category_name,
                            'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                            'created_at' => $app->created_at,
                        ]),
                ]
            )),
        ]);
    }
}
