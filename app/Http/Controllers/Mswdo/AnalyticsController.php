<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;
use App\Models\Application;
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

        return Inertia::render('Mswdo/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo) {
                $base = Application::whereBetween('applications.created_at', [$dateFrom, $dateTo]);
                $days = max(1, $dateFrom->diffInDays($dateTo) + 1);

                $pendingStatuses = ['submitted', 'mswdo_review'];
                $approvedStatuses = [
                    'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
                    'voucher_returned', 'voucher_checking', 'budget_checking',
                    'with_treasurer', 'cheque_ready', 'on_hold', 'claimed',
                ];

                return [
                    'total_applications' => (clone $base)->count(),
                    'total_pending' => (clone $base)->whereIn('status', $pendingStatuses)->count(),
                    'total_approved' => (clone $base)->whereIn('status', $approvedStatuses)->count(),
                    'average_per_day' => round((clone $base)->count() / $days, 1),

                    'trend' => (clone $base)
                        ->selectRaw('DATE(applications.created_at) as date, COUNT(*) as count')
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
                ];
            }),
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
        ]);
    }
}
