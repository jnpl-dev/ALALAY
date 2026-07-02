<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;

class ApplicationController extends Controller
{
    public function index() { return inertia('Aics/Applications/Index'); }
    public function show($app) { return inertia('Aics/Applications/Review'); }
    public function documentUrl($app, $doc) { abort(404); }
    public function approve(\Illuminate\Http\Request $r, $app) { return redirect()->route('aics.applications.index'); }
    public function return(\Illuminate\Http\Request $r, $app) { return redirect()->route('aics.applications.index'); }
}
