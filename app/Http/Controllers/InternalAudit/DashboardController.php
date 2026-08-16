<?php

namespace App\Http\Controllers\InternalAudit;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Review;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $weekStart = now()->subDays(6)->startOfDay();

        return Inertia::render('InternalAudit/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $weekStart) {
                $reviews = Review::byStage('internal_audit_review');

                $approvedIds = (clone $reviews)->where('decision', 'approved')
                    ->where('created_at', '>=', $weekStart)
                    ->distinct()->pluck('application_id');

                return [
                    'pending_reviews' => Application::where('status', 'internal_audit_review')->count(),
                    'approved_today' => (clone $reviews)
                        ->where('decision', 'approved')->whereDate('created_at', $today)->count(),
                    'returned_today' => (clone $reviews)
                        ->where('decision', 'returned')->whereDate('created_at', $today)->count(),

                    'decision_trend' => (clone $reviews)
                        ->where('created_at', '>=', $weekStart)
                        ->selectRaw('DATE(created_at) as date, decision, COUNT(*) as count')
                        ->groupBy('date', 'decision')->orderBy('date')->get(),

                    'decision_counts' => [
                        'approved' => (clone $reviews)->where('decision', 'approved')
                            ->where('created_at', '>=', $weekStart)->count(),
                        'returned' => (clone $reviews)->where('decision', 'returned')
                            ->where('created_at', '>=', $weekStart)->count(),
                    ],

                    'approved_amount_by_category' => Application::whereIn('id', $approvedIds)
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