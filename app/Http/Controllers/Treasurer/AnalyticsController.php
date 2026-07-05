<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());

        $chequesForProcessing = Application::where('status', 'with_treasurer')->count();
        $acknowledgedThisMonth = Application::whereIn('status', ['cheque_ready', 'claimed'])
            ->whereMonth('created_at', now()->month)
            ->count();
        $totalAmount = Application::whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])
            ->sum('amount_granted');
        $pendingCheques = Application::where('status', 'with_treasurer')->count();

        $monthlyTrends = Application::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count, sum(amount_granted) as total")
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentCheques = Application::with('category', 'encoder')
            ->whereIn('status', ['with_treasurer', 'cheque_ready', 'claimed'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'reference_code' => $app->reference_code,
                'status' => $app->status,
                'category_name' => $app->category?->category_name,
                'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                'amount' => $app->amount_granted,
                'created_at' => $app->created_at,
            ]);

        return Inertia::render('Treasurer/Analytics', [
            'chequesForProcessing' => $chequesForProcessing,
            'acknowledgedThisMonth' => $acknowledgedThisMonth,
            'totalAmount' => $totalAmount,
            'pendingCheques' => $pendingCheques,
            'monthlyTrends' => $monthlyTrends,
            'dateFrom' => $from,
            'dateTo' => $to,
            'recentCheques' => $recentCheques,
        ]);
    }
}
