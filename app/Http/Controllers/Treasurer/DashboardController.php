<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $weekStart = now()->subDays(6)->startOfDay();
        $treasurerStatuses = ['with_treasurer', 'cheque_ready', 'claimed'];

        return Inertia::render('Treasurer/Dashboard', [
            'dashboardData' => Inertia::defer(function () use ($today, $weekStart, $treasurerStatuses) {
                return [
                    'pending_cheques' => Application::where('status', 'with_treasurer')->count(),
                    'ready_today' => Application::where('status', 'cheque_ready')
                        ->whereDate('updated_at', $today)->count(),

                    'weekly_status_trend' => Application::whereIn('status', ['with_treasurer', 'cheque_ready'])
                        ->where('applications.updated_at', '>=', $weekStart)
                        ->selectRaw('DATE(applications.updated_at) as date, status, COUNT(*) as count')
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
