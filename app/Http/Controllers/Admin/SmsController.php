<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\Application;
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
}
