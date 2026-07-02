<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AssistanceCategoryController extends Controller
{
    public function index() { return inertia('Admin/AssistanceCategories/Index'); }
    public function create() { return inertia('Admin/AssistanceCategories/Create'); }
    public function store(\Illuminate\Http\Request $r) { return redirect()->route('admin.assistance-categories.index'); }
    public function edit($id) { return inertia('Admin/AssistanceCategories/Edit'); }
    public function update(\Illuminate\Http\Request $r, $id) { return redirect()->route('admin.assistance-categories.index'); }
    public function destroy($id) { return redirect()->route('admin.assistance-categories.index'); }
}
