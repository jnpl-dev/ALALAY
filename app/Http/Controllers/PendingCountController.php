<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingCountController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();
        $counts = [];

        match ($user->role) {
            'aics_staff' => [
                $counts['applications'] = Application::where('status', 'submitted')->count(),
            ],
            'mswdo' => [
                $counts['applications'] = Application::whereIn('status', ['submitted', 'mswdo_review'])->count(),
                $counts['vouchers'] = Application::where('status', 'voucher_creation')->count(),
            ],
            'accountant' => [
                $counts['vouchers'] = Application::where('status', 'voucher_recording')->count(),
            ],
            'treasurer' => [
                $counts['cheques'] = Application::where('status', 'with_treasurer')->count(),
            ],
            'internal_audit' => [
                $counts['applications'] = Application::where('status', 'internal_audit_review')->count(),
            ],
            'budget_officer' => [
                $counts['applications'] = Application::whereIn('status', ['budget_checking', 'voucher_on_hold'])->count(),
            ],
            'admin' => [
                $counts['applications'] = Application::where('status', 'submitted')->count(),
            ],
            default => [],
        };

        return response()->json($counts);
    }
}
