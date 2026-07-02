<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class RequiredDocumentController extends Controller
{
    public function index() { return inertia('Admin/RequiredDocuments/Index'); }
    public function create() { return inertia('Admin/RequiredDocuments/Create'); }
    public function store(\Illuminate\Http\Request $r) { return redirect()->route('admin.required-documents.index'); }
    public function edit($id) { return inertia('Admin/RequiredDocuments/Edit'); }
    public function update(\Illuminate\Http\Request $r, $id) { return redirect()->route('admin.required-documents.index'); }
    public function destroy($id) { return redirect()->route('admin.required-documents.index'); }
}
