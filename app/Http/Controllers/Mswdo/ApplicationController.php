<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;

class ApplicationController extends Controller
{
    public function index() { return inertia('Mswdo/Applications/Index'); }
    public function show($app) { return inertia('Mswdo/Applications/Review'); }
    public function approve(\Illuminate\Http\Request $r, $app) { return redirect()->route('mswdo.applications.index'); }
    public function return(\Illuminate\Http\Request $r, $app) { return redirect()->route('mswdo.applications.index'); }
}
