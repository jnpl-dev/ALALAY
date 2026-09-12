<?php

namespace App\Http\Controllers\Mswdo;

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

        return Inertia::render('Mswdo/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $yesterday, $weekStart, $lastWeekStart, $lastWeekEnd) {
                $mswdoFlowIds = Review::where('to_status', 'mswdo_review')
                    ->where('created_at', '>=', $weekStart)
                    ->pluck('application_id');
                $mswdoFlow = Application::whereIn('applications.id', $mswdoFlowIds);

                return [
                    'pending_applications' => Application::where('status', 'mswdo_review')->count(),
                    'pending_applications_change' => Review::where('to_status', 'mswdo_review')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'mswdo_review')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'approved_today' => Review::where('to_status', 'assistance_coding')
                        ->whereDate('created_at', $today)->count(),
                    'approved_yesterday' => Review::where('to_status', 'assistance_coding')
                        ->whereDate('created_at', $yesterday)->count(),
                    'approved_change' => Review::where('to_status', 'assistance_coding')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'assistance_coding')
                            ->whereDate('created_at', $yesterday)->count(),
                    'pending_voucher_creation' => Application::where('status', 'voucher_creation')->count(),
                    'pending_voucher_creation_change' => Review::where('to_status', 'voucher_creation')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'voucher_creation')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'vouchers_created_today' => Review::where('to_status', 'budget_checking')
                        ->whereDate('created_at', $today)->count(),
                    'vouchers_created_yesterday' => Review::where('to_status', 'budget_checking')
                        ->whereDate('created_at', $yesterday)->count(),
                    'vouchers_created_change' => Review::where('to_status', 'budget_checking')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'budget_checking')
                            ->whereDate('created_at', $yesterday)->count(),

                    'weekly_trend' => $this->fillWeekDates($weekStart,
                        Review::where('to_status', 'mswdo_review')
                            ->where('created_at', '>=', $weekStart)
                            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                            ->groupBy('date')->get()
                    ),

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
                        ->whereIn('applications.id', $mswdoFlowIds)
                        ->orderByDesc('updated_at')->limit(5)
                        ->get(['id', 'reference_code', 'beneficiary_first_name',
                            'beneficiary_last_name', 'category_id',
                            'submission_type', 'status', 'updated_at']),
                ];
            }),
        ]);
    }
}
