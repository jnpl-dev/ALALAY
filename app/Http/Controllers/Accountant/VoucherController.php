<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;

class VoucherController extends Controller
{
    public function index() { return inertia('Accountant/Vouchers/Index'); }
    public function show($v) { return inertia('Accountant/Vouchers/Review'); }
    public function approve(\Illuminate\Http\Request $r, $v) { return redirect()->route('accountant.vouchers.index'); }
    public function return(\Illuminate\Http\Request $r, $v) { return redirect()->route('accountant.vouchers.index'); }
}
