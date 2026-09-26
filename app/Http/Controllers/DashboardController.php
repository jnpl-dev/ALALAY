<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $route = match ($user?->role) {
            'admin' => 'admin.dashboard',
            'aics_staff' => 'aics.dashboard',
            'mswdo' => 'mswdo.dashboard',
            'accountant' => 'accountant.dashboard',
            'treasurer' => 'treasurer.dashboard',
            'internal_audit' => 'internal-audit.dashboard',
            'budget_officer' => 'budget-office.dashboard',
            default => 'account.edit',
        };

        return redirect()->route($route);
    }
}
