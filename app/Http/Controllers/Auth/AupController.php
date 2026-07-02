<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        $request->user()->update([
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
