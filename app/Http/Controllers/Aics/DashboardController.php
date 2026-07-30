<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $weekStart = now()->subDays(6)->startOfDay();

        return Inertia::render('Aics/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $weekStart) {
                return [
                    'pending_applications' => Application::where('status', 'submitted')->count(),
                    'screened_today' => Application::where('status', 'mswdo_review')
                        ->whereDate('updated_at', $today)->count(),
                    'pending_coding' => Application::where('status', 'assistance_coding')->count(),
                    'coded_today' => Application::where('status', 'voucher_creation')
                        ->whereDate('updated_at', $today)->count(),

                    'weekly_trend' => Application::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->where('created_at', '>=', $weekStart)
                        ->groupBy('date')->orderBy('date')->get(),

                    'category_distribution' => Application::selectRaw(
                        'assistance_categories.category_name, COUNT(*) as count'
                    )->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->where('applications.created_at', '>=', $weekStart)
                        ->groupBy('assistance_categories.category_name')->get(),

                    'submission_type_distribution' => Application::selectRaw(
                        'submission_type, COUNT(*) as count'
                    )->where('created_at', '>=', $weekStart)
                        ->whereNotNull('submission_type')
                        ->groupBy('submission_type')->get(),

                    'barangay_distribution' => Application::selectRaw(
                        'beneficiary_barangay as barangay, COUNT(*) as count'
                    )->where('created_at', '>=', $weekStart)
                        ->whereNotNull('beneficiary_barangay')
                        ->groupBy('beneficiary_barangay')
                        ->orderByDesc('count')->limit(10)->get(),

                    'recent_applications' => Application::with('category')
                        ->orderByDesc('created_at')->limit(5)
                        ->get(['id', 'reference_code', 'beneficiary_first_name',
                            'beneficiary_last_name', 'category_id',
                            'submission_type', 'status', 'created_at']),
                ];
            }),
        ]);
    }
}
