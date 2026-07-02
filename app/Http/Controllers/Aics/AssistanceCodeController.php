<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;

class AssistanceCodeController extends Controller
{
    public function index() { return inertia('Aics/AssistanceCodes/Index'); }
    public function show($app) { return inertia('Aics/AssistanceCodes/Code'); }
    public function store(\Illuminate\Http\Request $r, $app) { return redirect()->route('aics.assistance-codes.index'); }
}
