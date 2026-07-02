<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    public function index() { return inertia('Admin/AuditLogs'); }
    public function export() { return redirect()->route('admin.audit-logs'); }
}
