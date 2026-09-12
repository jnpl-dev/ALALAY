<?php

namespace App\Http\Controllers\Aics;

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

        return Inertia::render('Aics/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $yesterday, $weekStart, $lastWeekStart, $lastWeekEnd) {
                return [
                    'pending_applications' => Application::where('status', 'submitted')->count(),
                    'pending_applications_change' => Review::where('to_status', 'mswdo_review')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'mswdo_review')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'screened_today' => Review::where('to_status', 'mswdo_review')
                        ->whereDate('created_at', $today)->count(),
                    'screened_yesterday' => Review::where('to_status', 'mswdo_review')
                        ->whereDate('created_at', $yesterday)->count(),
                    'screened_change' => Review::where('to_status', 'mswdo_review')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'mswdo_review')
                            ->whereDate('created_at', $yesterday)->count(),
                    'pending_coding' => Application::whereIn('status', ['assistance_coding', 'returned_assistance_coding'])->count(),
                    'pending_coding_change' => Review::where('to_status', 'assistance_coding')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'assistance_coding')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'coded_today' => Review::where('to_status', 'internal_audit_review')
                        ->whereDate('created_at', $today)->count(),
                    'coded_yesterday' => Review::where('to_status', 'internal_audit_review')
                        ->whereDate('created_at', $yesterday)->count(),
                    'coded_change' => Review::where('to_status', 'internal_audit_review')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'internal_audit_review')
                            ->whereDate('created_at', $yesterday)->count(),

                    'weekly_trend' => $this->fillWeekDates($weekStart,
                        Application::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                            ->where('created_at', '>=', $weekStart)
                            ->groupBy('date')->get()
                    ),

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
