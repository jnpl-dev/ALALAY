<?php

namespace App\Http\Controllers\MayorsOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $approvedStatuses = ['social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_returned', 'voucher_checking', 'budget_checking', 'with_treasurer',
            'cheque_ready', 'claimed'];

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

        return Inertia::render('MayorsOffice/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo, $approvedStatuses, $stageGroups) {
                $base = Application::whereBetween('applications.created_at', [$dateFrom, $dateTo]);
                $total = (clone $base)->count();
                $approved = (clone $base)->whereIn('status', $approvedStatuses)->count();
                $claimed = (clone $base)->where('status', 'claimed')->count();

                return [
                    'total_applications' => $total,
                    'total_approved' => $approved,
                    'total_claimed' => $claimed,
                    'total_on_hold' => (clone $base)->where('status', 'on_hold')->count(),
                    'total_disbursed' => (clone $base)->where('applications.status', 'claimed')
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->sum('assistance_codes.amount'),
                    'average_amount' => $claimed > 0
                        ? round((clone $base)->where('applications.status', 'claimed')
                            ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                            ->sum('assistance_codes.amount') / $claimed, 2)
                        : 0,
                    'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,

                    'avg_days_to_cheque_ready' => Review::where('to_status', 'cheque_ready')
                        ->whereBetween('reviews.created_at', [$dateFrom, $dateTo])
                        ->join('applications', 'reviews.application_id', '=', 'applications.id')
                        ->selectRaw('COALESCE(ROUND(AVG(DATEDIFF(reviews.created_at, applications.created_at))), 0) as avg')
                        ->value('avg') ?? 0,

                    'trend' => (clone $base)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),

                    'category_distribution' => (clone $base)
                        ->selectRaw('assistance_categories.category_name, COUNT(*) as count')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'submission_type' => (clone $base)
                        ->selectRaw('submission_type, COUNT(*) as count')
                        ->whereNotNull('submission_type')
                        ->groupBy('submission_type')->get(),

                    'barangay_distribution' => (clone $base)
                        ->selectRaw('beneficiary_barangay as barangay, COUNT(*) as count')
                        ->whereNotNull('beneficiary_barangay')
                        ->groupBy('beneficiary_barangay')
                        ->orderByDesc('count')->limit(10)->get(),

                    'monthly_disbursement' => Application::where('applications.status', 'claimed')
                        ->whereBetween('applications.created_at', [$dateFrom, $dateTo])
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, SUM(assistance_codes.amount) as total")
                        ->groupBy('month')->orderBy('month')->get(),

                    'pipeline_bottleneck' => collect($stageGroups)->map(function ($statuses, $label) {
                        return [
                            'stage' => $label,
                            'count' => Application::whereIn('status', $statuses)->count(),
                        ];
                    })->values(),
                ];
            }),
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
        ]);
    }
}
