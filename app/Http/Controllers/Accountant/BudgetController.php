<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;

class BudgetController extends Controller
{
    public function index() { return inertia('Accountant/Budget/Index'); }
    public function show($v) { return inertia('Accountant/Budget/Check'); }
    public function markReady(\Illuminate\Http\Request $r, $v) { return redirect()->route('accountant.budget.index'); }
    public function hold(\Illuminate\Http\Request $r, $v) { return redirect()->route('accountant.budget.index'); }
    public function reEvaluate(\Illuminate\Http\Request $r, $v) { return redirect()->route('accountant.budget.index'); }
}
