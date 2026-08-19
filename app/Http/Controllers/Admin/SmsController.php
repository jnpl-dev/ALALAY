<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\Application;
use App\Models\SmsNotification;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SmsController extends Controller
{
    protected array $updateKeys = [
        'sms_template_submission_complete',
        'sms_template_under_review',
        'sms_template_resubmission_needed',
        'sms_template_cheque_ready',
    ];

    protected string $claimingKey = 'sms_template_cheque_claiming';

    public function updates()
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/Sms/Updates', [
            'templates' => Inertia::defer(fn () =>
                collect($this->updateKeys)->map(fn ($key) => [
                    'key' => $key,
                    'value' => SystemSetting::byKey($key)->first()?->setting_value ?? '',
                ])
            ),
        ]);
    }

    public function saveUpdates(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $data = $request->validate([
            'templates' => 'required|array',
            'templates.*.key' => 'required|string|in:' . implode(',', $this->updateKeys),
            'templates.*.value' => 'nullable|string|max:500',
        ]);

        foreach ($data['templates'] as $template) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $template['key']],
                [
                    'setting_value' => $template['value'] ?? '',
                    'updated_by' => $request->user()->id,
                ]
            );
            Cache::forget("settings.{$template['key']}");
        }

        return redirect()->route('admin.sms.updates')
            ->with('success', 'SMS templates updated.');
    }

    public function claiming()
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/Sms/Claiming', [
            'template' => Inertia::defer(fn () =>
                SystemSetting::byKey($this->claimingKey)->first()?->setting_value ?? ''
            ),
            'readyCount' => Application::where('status', 'cheque_ready')->count(),
        ]);
    }

    public function saveClaimingTemplate(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $data = $request->validate([
            'value' => 'nullable|string|max:500',
        ]);

        SystemSetting::updateOrCreate(
            ['setting_key' => $this->claimingKey],
            [
                'setting_value' => $data['value'] ?? '',
                'updated_by' => $request->user()->id,
            ]
        );
        Cache::forget("settings.{$this->claimingKey}");

        return redirect()->route('admin.sms.claiming')
            ->with('success', 'Claiming template updated.');
    }

    public function triggerClaiming(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $data = $request->validate([
            'claiming_date' => 'required|date|after_or_equal:today',
        ]);

        $claimingDate = $data['claiming_date'];
        $applications = Application::where('status', 'cheque_ready')->get();

        if ($applications->isEmpty()) {
            return redirect()->route('admin.sms.claiming')
                ->with('error', 'No cheque_ready applications to notify.');
        }

        DB::transaction(function () use ($applications, $claimingDate) {
            foreach ($applications as $application) {
                $application->update(['claiming_date' => $claimingDate]);
                SendSmsJob::dispatch($application, 'cheque_claiming');
            }
        });

        $count = $applications->count();
        return redirect()->route('admin.sms.claiming')
            ->with('success', "Claiming SMS sent to {$count} applicant(s) for {$claimingDate}.");
    }

    public function logs()
    {
        $this->authorize('viewAny', SystemSetting::class);

        $search = request('search');
        $event = request('event');
        $status = request('status');
        $from = request('from');
        $to = request('to');

        $logs = SmsNotification::with('application')
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('recipient_phone', 'like', "%{$s}%")
                  ->orWhere('message_body', 'like', "%{$s}%")
                  ->orWhere('trigger_event', 'like', "%{$s}%")
                  ->orWhereHas('application', fn ($q) => $q->where('reference_code', 'like', "%{$s}%"));
            }))
            ->when($event, fn ($q, $e) => $q->where('trigger_event', $e))
            ->when($status, fn ($q, $st) => $q->where('status', $st))
            ->when($from, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($to, fn ($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest('created_at')
            ->paginate(20)
            ->through(fn ($log) => [
                'id' => $log->id,
                'reference_code' => $log->application?->reference_code ?? '—',
                'recipient_phone' => $log->recipient_phone,
                'trigger_event' => $log->trigger_event,
                'event_label' => $this->eventLabel($log->trigger_event),
                'status' => $log->status,
                'status_label' => str($log->status)->headline(),
                'message_body' => $log->message_body,
                'provider_response' => $log->provider_response,
                'created_at' => $log->created_at,
            ]);

        $events = SmsNotification::distinct()->pluck('trigger_event')->sort()->values();
        $statuses = collect(['pending', 'sent', 'failed']);

        return Inertia::render('Admin/Sms/Logs', [
            'logs' => Inertia::defer(fn () => $logs),
            'filters' => request()->only(['search', 'event', 'status', 'from', 'to']),
            'events' => $events,
            'statuses' => $statuses,
        ]);
    }

    public function logsExport()
    {
        $this->authorize('viewAny', SystemSetting::class);

        $search = request('search');
        $event = request('event');
        $status = request('status');
        $from = request('from');
        $to = request('to');

        $logs = SmsNotification::with('application')
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('recipient_phone', 'like', "%{$s}%")
                  ->orWhere('message_body', 'like', "%{$s}%")
                  ->orWhere('trigger_event', 'like', "%{$s}%")
                  ->orWhereHas('application', fn ($q) => $q->where('reference_code', 'like', "%{$s}%"));
            }))
            ->when($event, fn ($q, $e) => $q->where('trigger_event', $e))
            ->when($status, fn ($q, $st) => $q->where('status', $st))
            ->when($from, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
            ->when($to, fn ($q, $t) => $q->whereDate('created_at', '<=', $t))
            ->latest('created_at')
            ->get();

        $filename = 'sms-logs-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Reference Code', 'Recipient', 'Event', 'Status', 'Message']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->toDateTimeString(),
                    $log->application?->reference_code,
                    $log->recipient_phone,
                    $log->trigger_event,
                    $log->status,
                    $log->message_body,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function eventLabel(string $event): string
    {
        return [
            'submission_complete' => 'Submission Complete',
            'application_under_review' => 'Under Review',
            'resubmission_needed' => 'Resubmission Needed',
            'cheque_ready' => 'Cheque Ready',
            'cheque_claiming' => 'Claiming Notice',
            'track_otp' => 'Tracking OTP',
        ][$event] ?? str($event)->headline();
    }
}
