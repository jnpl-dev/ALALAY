<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Voucher;
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

        return Inertia::render('Accountant/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo) {
                $vouchers = Voucher::whereBetween('vouchers.created_at', [$dateFrom, $dateTo]);
                $approvedVouchers = (clone $vouchers)->whereNull('returned_at');
                $returnedVouchers = (clone $vouchers)->whereNotNull('returned_at');

                $totalApproved = (clone $approvedVouchers)->count();

                return [
                    'total_vouchers' => (clone $vouchers)->count(),
                    'total_approved' => $totalApproved,
                    'total_returned' => (clone $returnedVouchers)->count(),
                    'total_amount_approved' => (clone $approvedVouchers)
                        ->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')
                        ->sum('assistance_codes.amount'),
                    'average_amount' => $totalApproved > 0
                        ? round((clone $approvedVouchers)
                            ->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')
                            ->sum('assistance_codes.amount') / $totalApproved, 2)
                        : 0,

                    'voucher_trend' => (clone $vouchers)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),

                    'amount_trend' => (clone $approvedVouchers)
                        ->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')
                        ->selectRaw('DATE(vouchers.created_at) as date, SUM(assistance_codes.amount) as total')
                        ->groupBy('date')->orderBy('date')->get(),

                    'category_amount' => Voucher::whereNull('returned_at')
                        ->whereBetween('vouchers.created_at', [$dateFrom, $dateTo])
                        ->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')
                        ->join('applications', 'assistance_codes.application_id', '=', 'applications.id')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')
                        ->orderByDesc('total')->get(),
                ];
            }),
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
        ]);
    }
}
