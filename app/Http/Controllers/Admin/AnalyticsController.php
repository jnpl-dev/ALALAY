<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCode;
use App\Models\AuditLog;
use App\Models\User;
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

        return Inertia::render('Admin/Analytics', [
            'analyticsData' => Inertia::defer(function () use ($dateFrom, $dateTo) {
                $apps = Application::whereBetween('created_at', [$dateFrom, $dateTo]);

                return [
                    'total_users_registered' => User::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                    'total_applications' => (clone $apps)->count(),
                    'total_vouchers' => Voucher::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                    'total_disbursed' => AssistanceCode::whereHas('application', fn ($q) =>
                        $q->where('status', 'claimed')->whereBetween('created_at', [$dateFrom, $dateTo])
                    )->sum('amount'),

                    'user_registration_trend' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->groupBy('date')->orderBy('date')->get(),

                    'application_status_distribution' => Application::selectRaw('status, COUNT(*) as count')
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->groupBy('status')->get(),

                    'monthly_disbursement_trend' => AssistanceCode::selectRaw("DATE_FORMAT(assistance_codes.created_at, '%Y-%m') as month, SUM(assistance_codes.amount) as total")
                        ->join('applications', 'assistance_codes.application_id', '=', 'applications.id')
                        ->where('applications.status', 'claimed')
                        ->whereBetween('assistance_codes.created_at', [$dateFrom, $dateTo])
                        ->groupBy('month')->orderBy('month')->get(),

                    'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                        ->groupBy('role')->get(),

                    'application_trend' => (clone $apps)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')->orderBy('date')->get(),
                ];
            }),
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }
}
