<?php

namespace App\Http\Controllers\InternalAudit;

use App\Http\Controllers\Controller;
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

        return Inertia::render('InternalAudit/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo) {
                $reviews = Review::byStage('internal_audit_review')
                    ->whereBetween('reviews.created_at', [$dateFrom, $dateTo]);

                $days = max(1, $dateFrom->diffInDays($dateTo) + 1);

                $approvedIds = (clone $reviews)->where('decision', 'approved')
                    ->distinct()->pluck('application_id');

                $codedAmount = AssistanceCode::whereIn('application_id', $approvedIds)
                    ->sum('amount');
                $codedCount = $approvedIds->count();

                return [
                    'total_reviewed' => (clone $reviews)->count(),
                    'average_reviewed_per_day' => round((clone $reviews)->count() / $days, 1),
                    'approved' => (clone $reviews)->where('decision', 'approved')->count(),
                    'returned' => (clone $reviews)->where('decision', 'returned')->count(),
                    'coded_amount' => $codedAmount,
                    'average_coded_amount' => $codedCount > 0 ? round($codedAmount / $codedCount, 2) : 0,
                    'approved_count' => $codedCount,

                    'decision_trend' => (clone $reviews)
                        ->selectRaw('DATE(created_at) as date, decision, COUNT(*) as count')
                        ->groupBy('date', 'decision')->orderBy('date')->get(),

                    'category_distribution' => (clone $reviews)
                        ->join('applications', 'applications.id', '=', 'reviews.application_id')
                        ->join('assistance_categories', 'assistance_categories.id', '=', 'applications.category_id')
                        ->selectRaw('assistance_categories.category_name, COUNT(*) as count')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'amount_by_category' => (clone $reviews)
                        ->where('decision', 'approved')
                        ->join('assistance_codes', 'assistance_codes.application_id', '=', 'reviews.application_id')
                        ->join('applications', 'applications.id', '=', 'reviews.application_id')
                        ->join('assistance_categories', 'assistance_categories.id', '=', 'applications.category_id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')->get(),
                ];
            }),
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }
}