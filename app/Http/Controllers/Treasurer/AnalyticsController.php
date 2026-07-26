<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
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

        $treasurerStatuses = ['with_treasurer', 'cheque_ready', 'on_hold', 'claimed'];

        return Inertia::render('Treasurer/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo, $treasurerStatuses) {
                $base = Application::whereIn('status', $treasurerStatuses)
                    ->whereBetween('applications.created_at', [$dateFrom, $dateTo]);

                return [
                    'total_processed' => (clone $base)->count(),
                    'total_cheque_ready' => (clone $base)->where('status', 'cheque_ready')->count(),
                    'total_on_hold' => (clone $base)->where('status', 'on_hold')->count(),
                    'total_claimed' => (clone $base)->where('status', 'claimed')->count(),
                    'total_amount_disbursed' => (clone $base)->where('status', 'claimed')
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->sum('assistance_codes.amount'),

                    'status_trend' => Application::whereIn('status', $treasurerStatuses)
                        ->whereBetween('applications.updated_at', [$dateFrom, $dateTo])
                        ->selectRaw('DATE(applications.updated_at) as date, status, COUNT(*) as count')
                        ->groupBy('date', 'status')->orderBy('date')->get(),

                    'status_distribution' => (clone $base)
                        ->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')->get(),

                    'amount_by_category' => Application::whereIn('applications.status', $treasurerStatuses)
                        ->whereBetween('applications.created_at', [$dateFrom, $dateTo])
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')
                        ->orderByDesc('total')->get(),

                    'amount_over_time' => Application::whereIn('applications.status', $treasurerStatuses)
                        ->whereBetween('applications.created_at', [$dateFrom, $dateTo])
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->selectRaw('DATE(applications.created_at) as date, SUM(assistance_codes.amount) as total')
                        ->groupBy('date')->orderBy('date')->get(),
                ];
            }),
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
        ]);
    }
}
