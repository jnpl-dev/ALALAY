<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AupController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/AcceptableUsePolicy');
    }

    public function accept(Request $request)
    {
        $user = $request->user();

        $user->update([
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'module' => 'auth',
            'action' => 'aup_accepted',
            'description' => sprintf('%s accepted the Acceptable Use Policy', $user->full_name),
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
