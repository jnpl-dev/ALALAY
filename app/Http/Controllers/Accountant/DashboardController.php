<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Review;
use App\Models\Voucher;
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

        return Inertia::render('Accountant/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $yesterday, $weekStart, $lastWeekStart, $lastWeekEnd) {
                $accountantStatuses = ['voucher_recording', 'budget_checking', 'with_treasurer'];

                return [
                    'pending_vouchers' => Application::where('status', 'voucher_recording')->count(),
                    'pending_vouchers_change' => Review::where('to_status', 'voucher_recording')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'voucher_recording')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'approved_today' => Review::where('to_status', 'with_treasurer')
                        ->whereDate('created_at', $today)->count(),
                    'approved_yesterday' => Review::where('to_status', 'with_treasurer')
                        ->whereDate('created_at', $yesterday)->count(),
                    'approved_change' => Review::where('to_status', 'with_treasurer')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'with_treasurer')
                            ->whereDate('created_at', $yesterday)->count(),

                    'weekly_voucher_trend' => $this->fillWeekDates($weekStart,
                        Voucher::where('created_at', '>=', $weekStart)
                            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                            ->groupBy('date')->get()
                    ),

                    'voucher_statuses' => [
                        ['status' => 'voucher_recording', 'count' => Application::where('status', 'voucher_recording')->count()],
                        ['status' => 'with_treasurer', 'count' => Application::where('status', 'with_treasurer')->count()],
                    ],

                    'category_amount' => Voucher::where('vouchers.created_at', '>=', $weekStart)
                        ->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')
                        ->join('applications', 'assistance_codes.application_id', '=', 'applications.id')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')
                        ->orderByDesc('total')->get(),

                    'recent_vouchers' => Voucher::with(['application.category', 'assistanceCode'])
                        ->latest()->limit(5)->get()
                        ->map(fn ($v) => [
                            'id' => $v->id,
                            'reference_code' => $v->application?->reference_code,
                            'beneficiary_name' => $v->application
                                ? trim(($v->application->beneficiary_first_name ?? '') . ' ' . ($v->application->beneficiary_last_name ?? ''))
                                : '—',
                            'category_name' => $v->application?->category?->category_name ?? '—',
                            'amount' => $v->assistanceCode?->amount,
                            'status' => $v->application?->status ?? '—',
                            'prepared_at' => $v->prepared_at,
                        ]),
                ];
            }),
        ]);
    }
}
