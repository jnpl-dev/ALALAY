<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;

class VoucherController extends Controller
{
    public function index() { return inertia('Mswdo/Vouchers/Index'); }
    public function show($app) { return inertia('Mswdo/Vouchers/Create'); }
    public function store(\Illuminate\Http\Request $r, $app) { return redirect()->route('mswdo.vouchers.index'); }
}
