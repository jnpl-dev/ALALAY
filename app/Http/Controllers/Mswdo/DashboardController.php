<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $weekStart = now()->subDays(6)->startOfDay();

        return Inertia::render('Mswdo/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $weekStart) {
                $excluded = ['submitted'];
                $mswdoFlow = Application::whereNotIn('status', $excluded)
                    ->where('applications.created_at', '>=', $weekStart);

                return [
                    'pending_applications' => Application::where('status', 'mswdo_review')->count(),
                    'approved_today' => Application::where('status', 'social_case_study_uploaded')
                        ->whereDate('updated_at', $today)->count(),
                    'pending_voucher_creation' => Application::whereIn('status', ['voucher_creation', 'voucher_returned'])->count(),
                    'vouchers_created_today' => Application::where('status', 'voucher_checking')
                        ->whereDate('updated_at', $today)->count(),

                    'weekly_trend' => (clone $mswdoFlow)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),

                    'category_distribution' => (clone $mswdoFlow)
                        ->selectRaw('assistance_categories.category_name, COUNT(*) as count')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'submission_type_distribution' => (clone $mswdoFlow)
                        ->selectRaw('submission_type, COUNT(*) as count')
                        ->whereNotNull('submission_type')
                        ->groupBy('submission_type')->get(),

                    'barangay_distribution' => (clone $mswdoFlow)
                        ->selectRaw('beneficiary_barangay as barangay, COUNT(*) as count')
                        ->whereNotNull('beneficiary_barangay')
                        ->groupBy('beneficiary_barangay')
                        ->orderByDesc('count')->limit(10)->get(),

                    'recent_applications' => Application::with('category')
                        ->whereNotIn('status', $excluded)
                        ->orderByDesc('updated_at')->limit(5)
                        ->get(['id', 'reference_code', 'beneficiary_first_name',
                            'beneficiary_last_name', 'category_id',
                            'submission_type', 'status', 'updated_at']),
                ];
            }),
        ]);
    }
}
