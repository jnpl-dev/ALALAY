<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;

class BudgetController extends Controller
{
    public function index() { return inertia('Treasurer/Budget/Index'); }
    public function show($v) { return inertia('Treasurer/Budget/Check'); }
    public function markReady(\Illuminate\Http\Request $r, $v) { return redirect()->route('treasurer.budget.index'); }
    public function hold(\Illuminate\Http\Request $r, $v) { return redirect()->route('treasurer.budget.index'); }
    public function reEvaluate(\Illuminate\Http\Request $r, $v) { return redirect()->route('treasurer.budget.index'); }
}
