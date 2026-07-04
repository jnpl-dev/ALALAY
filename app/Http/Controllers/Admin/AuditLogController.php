<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index()
    {
        $search = request('search');
        $module = request('module');
        $action = request('action');
        $from = request('from');
        $to = request('to');

        $logs = AuditLog::with('user')
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('module', 'like', "%{$s}%")
                  ->orWhere('action', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            }))
            ->when($module, fn ($q, $m) => $q->where('module', $m))
            ->when($action, fn ($q, $a) => $q->where('action', $a))
            ->when($from, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($to, fn ($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest('created_at')
            ->paginate(20)
            ->through(fn ($log) => [
                'id' => $log->id,
                'user_name' => $log->user?->full_name ?? 'System',
                'role' => $log->role,
                'role_label' => (fn ($r) => [
                    'admin' => 'Admin',
                    'aics_staff' => 'AICS',
                    'mswdo' => 'MSWDO',
                    'accountant' => 'Accountant',
                    'treasurer' => 'Treasurer',
                    'mayors_office' => "Mayor's Office",
                ][$r] ?? $r)($log->role),
                'module' => $log->module,
                'module_label' => (fn ($m) => [
                    'auth' => 'Authentication',
                    'users' => 'User Management',
                    'admin' => 'Administration',
                    'aics' => 'AICS',
                    'mswdo' => 'MSWDO',
                    'accountant' => 'Accountant',
                    'treasurer' => 'Treasurer',
                    'mayors_office' => "Mayor's Office",
                    'applications' => 'Applications',
                    'assistance-categories' => 'Assistance Categories',
                    'required-documents' => 'Required Documents',
                    'general' => 'General',
                ][$m] ?? str($m)->headline())($log->module),
                'action' => $log->action,
                'action_label' => (fn ($a) => [
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'aup_accepted' => 'AUP Accepted',
                    'store' => 'Created',
                    'update' => 'Updated',
                    'destroy' => 'Deleted',
                    'toggle-status' => 'Status Toggled',
                    'revoke-sessions' => 'Sessions Revoked',
                    'index' => 'Viewed List',
                    'show' => 'Viewed Details',
                    'create' => 'Opened Create Form',
                    'edit' => 'Opened Edit Form',
                    'export' => 'Exported',
                    'resend' => 'Resent OTP',
                    'verify' => 'Verified',
                    'accept' => 'Accepted',
                ][$a] ?? str($a)->headline())($log->action),
                'description' => $log->description,
                'entity_type' => $log->entity_type,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ]);

        $modules = AuditLog::distinct()->pluck('module')->sort()->values();
        $actions = AuditLog::distinct()->pluck('action')->sort()->values();

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
            'filters' => request()->only(['search', 'module', 'action', 'from', 'to']),
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }

    public function export()
    {
        $search = request('search');
        $module = request('module');
        $action = request('action');
        $from = request('from');
        $to = request('to');

        $logs = AuditLog::with('user')
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('module', 'like', "%{$s}%")
                  ->orWhere('action', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            }))
            ->when($module, fn ($q, $m) => $q->where('module', $m))
            ->when($action, fn ($q, $a) => $q->where('action', $a))
            ->when($from, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($to, fn ($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest('created_at')
            ->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'User', 'Role', 'Module', 'Action', 'Description', 'Entity Type', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->toDateTimeString(),
                    $log->user?->full_name ?? 'System',
                    $log->role,
                    $log->module,
                    $log->action,
                    $log->description,
                    $log->entity_type,
                    $log->ip_address,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
