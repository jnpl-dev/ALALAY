<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index() { return inertia('Admin/Users/Index'); }
    public function create() { return inertia('Admin/Users/Create'); }
    public function store(\Illuminate\Http\Request $r) { return redirect()->route('admin.users.index'); }
    public function edit($user) { return inertia('Admin/Users/Edit'); }
    public function update(\Illuminate\Http\Request $r, $user) { return redirect()->route('admin.users.index'); }
    public function destroy($user) { return redirect()->route('admin.users.index'); }
    public function toggleStatus($user) { return redirect()->route('admin.users.index'); }
    public function revokeSessions($user) { return redirect()->route('admin.users.index'); }
}
