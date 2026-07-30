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
                $counts['applications'] = Application::whereIn('status', ['submitted', 'screening'])->count(),
            ],
            'mswdo' => [
                $counts['applications'] = Application::whereIn('status', ['submitted', 'screening', 'mswdo_review'])->count(),
                $counts['vouchers'] = Application::whereIn('status', ['voucher_creation', 'voucher_returned'])->count(),
            ],
            'accountant' => [
                $counts['vouchers'] = Application::where('status', 'voucher_checking')->count(),
            ],
            'treasurer' => [
                $counts['cheques'] = Application::where('status', 'with_treasurer')->count(),
            ],
            'mayors_office' => [
                $counts['analytics'] = Application::whereIn('status', ['claimed', 'cheque_ready'])->count(),
            ],
            'admin' => [
                $counts['applications'] = Application::whereIn('status', ['submitted', 'screening'])->count(),
            ],
            default => [],
        };

        return response()->json($counts);
    }
}
