<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());

        $vouchersForReview = Application::whereIn('status', ['voucher_creation', 'voucher_checking'])->count();
        $approvedThisMonth = Application::whereIn('status', ['budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])
            ->whereMonth('created_at', now()->month)
            ->count();
        $totalAmount = Application::whereIn('applications.status', [
            'voucher_creation', 'voucher_checking', 'budget_checking',
            'with_treasurer', 'cheque_ready', 'claimed',
        ])->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
            ->sum('assistance_codes.amount');

        $disbursedThisMonth = Application::where('applications.status', 'claimed')
            ->whereMonth('applications.created_at', now()->month)
            ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
            ->sum('assistance_codes.amount');

        $monthlyTrends = Application::selectRaw("DATE_FORMAT(applications.created_at, '%Y-%m') as month, count(*) as count, sum(assistance_codes.amount) as total")
            ->whereBetween('applications.created_at', [$from, $to . ' 23:59:59'])
            ->whereIn('applications.status', ['voucher_creation', 'voucher_checking', 'budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])
            ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentTransactions = Application::with('category', 'encoder', 'assistanceCode')
            ->whereIn('status', ['voucher_creation', 'voucher_checking', 'budget_checking', 'with_treasurer', 'cheque_ready', 'claimed'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'reference_code' => $app->reference_code,
                'status' => $app->status,
                'category_name' => $app->category?->category_name,
                'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                'amount' => $app->assistanceCode?->amount,
                'created_at' => $app->created_at,
            ]);

        return Inertia::render('Accountant/Analytics', [
            'vouchersForReview' => $vouchersForReview,
            'approvedThisMonth' => $approvedThisMonth,
            'totalAmount' => $totalAmount,
            'disbursedThisMonth' => $disbursedThisMonth,
            'monthlyTrends' => $monthlyTrends,
            'dateFrom' => $from,
            'dateTo' => $to,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
