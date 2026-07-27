<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Voucher;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $weekStart = now()->subDays(6)->startOfDay();

        return Inertia::render('Accountant/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $weekStart) {
                $accountantStatuses = ['voucher_checking', 'voucher_returned', 'budget_checking', 'with_treasurer'];

                return [
                    'pending_vouchers' => Application::where('status', 'voucher_checking')->count(),
                    'approved_today' => Application::where('status', 'with_treasurer')
                        ->whereDate('updated_at', $today)->count(),
                    'returned_today' => Voucher::whereDate('returned_at', $today)->count(),

                    'weekly_voucher_trend' => Voucher::where('created_at', '>=', $weekStart)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),

                    'voucher_statuses' => [
                        ['status' => 'voucher_checking', 'count' => Application::where('status', 'voucher_checking')->count()],
                        ['status' => 'with_treasurer', 'count' => Application::where('status', 'with_treasurer')->count()],
                        ['status' => 'returned', 'count' => Voucher::whereNotNull('returned_at')->count()],
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
                            'returned_at' => $v->returned_at,
                            'prepared_at' => $v->prepared_at,
                        ]),
                ];
            }),
        ]);
    }
}
