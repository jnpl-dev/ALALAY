<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
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
        $treasurerStatuses = ['with_treasurer', 'cheque_ready', 'claimed'];

        return Inertia::render('Treasurer/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $yesterday, $weekStart, $lastWeekStart, $lastWeekEnd, $treasurerStatuses) {
                return [
                    'pending_cheques' => Application::where('status', 'with_treasurer')->count(),
                    'pending_cheques_change' => Review::where('to_status', 'with_treasurer')
                        ->where('created_at', '>=', $weekStart)->count()
                        - Review::where('to_status', 'with_treasurer')
                            ->where('created_at', '>=', $lastWeekStart)->where('created_at', '<', $weekStart)->count(),
                    'ready_today' => Review::where('to_status', 'cheque_ready')
                        ->whereDate('created_at', $today)->count(),
                    'ready_yesterday' => Review::where('to_status', 'cheque_ready')
                        ->whereDate('created_at', $yesterday)->count(),
                    'ready_change' => Review::where('to_status', 'cheque_ready')
                        ->whereDate('created_at', $today)->count()
                        - Review::where('to_status', 'cheque_ready')
                            ->whereDate('created_at', $yesterday)->count(),
                    'claimed_count' => Application::where('status', 'claimed')->count(),
                    'claimed_change' => Application::where('status', 'claimed')
                        ->whereNotNull('claimed_at')
                        ->selectRaw('DATE(claimed_at) as claim_date, COUNT(*) as count')
                        ->groupBy('claim_date')
                        ->orderByDesc('claim_date')
                        ->limit(2)->get()
                        ->pipe(fn ($dates) => $dates->count() === 2
                            ? $dates[0]->count - $dates[1]->count
                            : null),

                    'weekly_status_trend' => Review::whereIn('to_status', ['with_treasurer', 'cheque_ready'])
                        ->where('created_at', '>=', $weekStart)
                        ->selectRaw('DATE(created_at) as date, to_status as status, COUNT(*) as count')
                        ->groupBy('date', 'status')->orderBy('date')->get(),

                    'status_distribution' => Application::whereIn('status', $treasurerStatuses)
                        ->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')->get(),

                    'amount_by_category' => Application::whereIn('applications.status', $treasurerStatuses)
                        ->join('assistance_codes', 'applications.id', '=', 'assistance_codes.application_id')
                        ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                        ->selectRaw('assistance_categories.category_name, SUM(assistance_codes.amount) as total')
                        ->groupBy('assistance_categories.category_name')
                        ->orderByDesc('total')->get(),

                    'recent_applications' => Application::with('category', 'assistanceCode')
                        ->whereIn('status', $treasurerStatuses)
                        ->latest('updated_at')->limit(5)->get()
                        ->map(fn ($app) => [
                            'id' => $app->id,
                            'reference_code' => $app->reference_code,
                            'beneficiary_name' => trim(($app->beneficiary_first_name ?? '') . ' ' . ($app->beneficiary_last_name ?? '')),
                            'category_name' => $app->category?->category_name ?? '—',
                            'amount' => $app->assistanceCode?->amount,
                            'status' => $app->status,
                            'updated_at' => $app->updated_at,
                        ]),
                ];
            }),
        ]);
    }
}
