<?php

namespace App\Http\Controllers\BudgetOffice;

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

        return Inertia::render('BudgetOffice/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo) {
                $reviews = Review::byStage('budget_checking')
                    ->whereBetween('reviews.created_at', [$dateFrom, $dateTo]);

                $days = max(1, $dateFrom->diffInDays($dateTo) + 1);

                $reviewedCount = (clone $reviews)->count();
                $approvedCount = (clone $reviews)->where('decision', 'approved')->count();

                $approvedIds = (clone $reviews)->where('decision', 'approved')
                    ->distinct()->pluck('application_id');

                $underReview = Application::whereIn('status', ['budget_checking', 'voucher_on_hold']);
                $underReviewAmount = (clone $underReview)
                    ->join('assistance_codes', 'assistance_codes.application_id', '=', 'applications.id')
                    ->sum('assistance_codes.amount');
                $underReviewCount = (clone $underReview)
                    ->whereHas('assistanceCode')->count();

                return [
                    'total_checked' => $reviewedCount,
                    'average_checked_per_day' => round($reviewedCount / $days, 1),
                    'approved' => $approvedCount,
                    'held' => (clone $reviews)->whereIn('decision', ['hold', 'on_hold'])->count(),
                    'under_review_amount' => $underReviewAmount,
                    'average_per_voucher' => $underReviewCount > 0
                        ? round($underReviewAmount / $underReviewCount, 2)
                        : 0,
                    'under_review_count' => $underReviewCount,

                    'decision_trend' => (clone $reviews)
                        ->selectRaw('DATE(created_at) as date, decision, COUNT(*) as count')
                        ->groupBy('date', 'decision')->orderBy('date')->get(),

                    'category_distribution' => (clone $reviews)
                        ->join('applications', 'applications.id', '=', 'reviews.application_id')
                        ->join('assistance_categories', 'assistance_categories.id', '=', 'applications.category_id')
                        ->selectRaw('assistance_categories.category_name, COUNT(*) as count')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'amount_by_category' => Application::whereIn('id', $approvedIds)
                        ->join('assistance_codes', 'assistance_codes.application_id', '=', 'applications.id')
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