<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SystemSettingController extends Controller
{
    public function index() { return inertia('Admin/SystemSettings'); }
    public function update(\Illuminate\Http\Request $r) { return redirect()->route('admin.settings'); }
}
