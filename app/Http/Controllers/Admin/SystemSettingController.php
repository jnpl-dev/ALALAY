<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SystemSettingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/SystemSettings', [
            'groups' => Inertia::defer(fn () =>
                SystemSetting::all()->groupBy('setting_group')
                    ->map(fn ($items, $group) => [
                        'group' => $group,
                        'settings' => $items->map(fn ($item) => [
                            'key' => $item->setting_key,
                            'value' => $item->setting_value,
                            'label' => str($item->setting_key)->replace('_', ' ')->title(),
                        ]),
                    ])->values()
            ),
            'isDownForMaintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('update', SystemSetting::class);
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'updated_by' => $request->user()->id,
                ]
            );
            Cache::forget("settings.{$key}");
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Settings updated successfully.');
    }

    public function toggleMaintenance(): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            $message = 'System is now online.';
            $action = 'maintenance_off';
        } else {
            Artisan::call('down', [
                '--secret' => config('app.maintenance_secret'),
                '--render' => 'errors.503',
            ]);
            $message = 'System is now in maintenance mode.';
            $action = 'maintenance_on';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'module' => 'system',
            'action' => $action,
            'description' => $message,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', $message);
    }
}
