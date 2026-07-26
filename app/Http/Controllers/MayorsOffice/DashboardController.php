<?php

namespace App\Http\Controllers\MayorsOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $monthStart = now()->startOfMonth();

        return Inertia::render('MayorsOffice/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $monthStart) {
                $stageGroups = [
                    'For Submission' => ['submitted'],
                    'MSWDO Review' => ['mswdo_review', 'social_case_study_uploaded'],
                    'Assistance Coding' => ['assistance_coding'],
                    'Voucher Processing' => ['voucher_creation', 'voucher_returned', 'voucher_checking', 'budget_checking'],
                    'With Treasurer' => ['with_treasurer'],
                    'Cheque Ready' => ['cheque_ready'],
                    'Claimed' => ['claimed'],
                    'On Hold' => ['on_hold'],
                    'Returned' => ['returned_to_applicant'],
                ];

                $approvedStatuses = ['social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
                    'voucher_returned', 'voucher_checking', 'budget_checking', 'with_treasurer',
                    'cheque_ready', 'claimed'];

                return [
                    'applications_today' => Application::whereDate('created_at', $today)->count(),
                    'approved_today' => Application::whereIn('status', $approvedStatuses)
                        ->whereDate('updated_at', $today)->count(),
                    'cheques_ready_today' => Application::where('status', 'cheque_ready')
                        ->whereDate('updated_at', $today)->count(),
                    'claimed_today' => Application::where('status', 'claimed')
                        ->whereDate('updated_at', $today)->count(),

                    'total_apps_month' => Application::where('created_at', '>=', $monthStart)->count(),
                    'total_approved_month' => Application::whereIn('status', $approvedStatuses)
                        ->where('created_at', '>=', $monthStart)->count(),
                    'total_disbursed_month' => Application::where('applications.status', 'claimed')
                        ->where('applications.created_at', '>=', $monthStart)
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->sum('assistance_codes.amount'),
                    'total_on_hold' => Application::where('status', 'on_hold')->count(),

                    'daily_volume' => Application::where('created_at', '>=', now()->subDays(6)->startOfDay())
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),

                    'pipeline_stages' => collect($stageGroups)->map(function ($statuses, $label) {
                        return [
                            'stage' => $label,
                            'count' => Application::whereIn('status', $statuses)->count(),
                        ];
                    })->values(),

                    'category_distribution' => Application::selectRaw(
                        'assistance_categories.category_name, COUNT(*) as count'
                    )->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'barangay_distribution' => Application::selectRaw(
                        'beneficiary_barangay as barangay, COUNT(*) as count'
                    )->whereNotNull('beneficiary_barangay')
                        ->groupBy('beneficiary_barangay')
                        ->orderByDesc('count')->limit(10)->get(),

                    'amount_by_category' => Application::join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')
                        ->orderByDesc('total')->get(),

                    'recent_applications' => Application::with('category', 'assistanceCode')
                        ->latest('updated_at')->limit(8)->get()
                        ->map(fn ($app) => [
                            'id' => $app->id,
                            'reference_code' => $app->reference_code,
                            'beneficiary_name' => trim(($app->beneficiary_first_name ?? '') . ' ' . ($app->beneficiary_last_name ?? '')),
                            'category_name' => $app->category?->category_name ?? '—',
                            'amount' => $app->assistanceCode?->amount,
                            'status' => $app->status,
                            'updated_at' => $app->updated_at,
                        ]),
                ];
            }),
        ]);
    }
}
