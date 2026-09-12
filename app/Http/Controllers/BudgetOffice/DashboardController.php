<?php

namespace App\Http\Controllers\BudgetOffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Review;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $yesterday = today()->subDay();
        $weekStart = now()->subDays(6)->startOfDay();
        $lastWeekStart = now()->subDays(13)->startOfDay();
        $lastWeekEnd = now()->subDays(7)->endOfDay();

        return Inertia::render('BudgetOffice/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $yesterday, $weekStart, $lastWeekStart, $lastWeekEnd) {
                $reviews = Review::byStage('budget_checking');

                return [
                    'pending_budget_checks' => Application::where('status', 'budget_checking')->count(),
                    'pending_budget_checks_change' => Review::where('to_status', 'budget_checking')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'budget_checking')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'on_hold' => Application::where('status', 'voucher_on_hold')->count(),
                    'on_hold_change' => Review::where('to_status', 'voucher_on_hold')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'voucher_on_hold')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'forwarded_today' => (clone $reviews)
                        ->where('decision', 'approved')->whereDate('created_at', $today)->count(),
                    'forwarded_yesterday' => (clone $reviews)
                        ->where('decision', 'approved')->whereDate('created_at', $yesterday)->count(),
                    'forwarded_change' => (clone $reviews)->where('decision', 'approved')
                        ->whereDate('created_at', $today)->count()
                        - (clone $reviews)->where('decision', 'approved')
                            ->whereDate('created_at', $yesterday)->count(),

                    'decision_trend' => (clone $reviews)
                        ->where('created_at', '>=', $weekStart)
                        ->selectRaw('DATE(created_at) as date, decision, COUNT(*) as count')
                        ->groupBy('date', 'decision')->orderBy('date')->get(),

                    'decision_counts' => [
                        'approved' => (clone $reviews)->where('decision', 'approved')
                            ->where('created_at', '>=', $weekStart)->count(),
                        'hold' => (clone $reviews)->where('decision', 'hold')
                            ->where('created_at', '>=', $weekStart)->count(),
                        'on_hold' => (clone $reviews)->where('decision', 'on_hold')
                            ->where('created_at', '>=', $weekStart)->count(),
                    ],

                    'under_review_amount_by_category' => Application::whereIn('status', ['budget_checking', 'voucher_on_hold'])
                        ->join('assistance_codes', 'assistance_codes.application_id', '=', 'applications.id')
                        ->join('assistance_categories', 'assistance_categories.id', '=', 'applications.category_id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')->get(),

                    'recent_reviews' => (clone $reviews)
                        ->with(['application.category', 'application.assistanceCode'])
                        ->latest()
                        ->limit(5)
                        ->get(['application_id', 'decision', 'created_at'])
                        ->map(fn ($r) => [
                            'reference_code' => $r->application?->reference_code,
                            'claimant_name' => $r->application
                                ? $r->application->claimant_first_name . ' ' . $r->application->claimant_last_name
                                : '—',
                            'category_name' => $r->application?->category?->category_name ?? '—',
                            'amount' => $r->application?->assistanceCode?->amount,
                            'decision' => $r->decision,
                            'reviewed_at' => $r->created_at,
                        ]),
                ];
            }),
        ]);
    }
}