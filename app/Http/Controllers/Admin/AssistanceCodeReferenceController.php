<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AssistanceCodeReferenceController extends Controller
{
    public function index() { return inertia('Admin/AssistanceCodeReferences/Index'); }
    public function create() { return inertia('Admin/AssistanceCodeReferences/Create'); }
    public function store(\Illuminate\Http\Request $r) { return redirect()->route('admin.assistance-code-references.index'); }
    public function edit($id) { return inertia('Admin/AssistanceCodeReferences/Edit'); }
    public function update(\Illuminate\Http\Request $r, $id) { return redirect()->route('admin.assistance-code-references.index'); }
    public function destroy($id) { return redirect()->route('admin.assistance-code-references.index'); }
}
