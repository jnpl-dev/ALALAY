<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SystemSettingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SystemSetting::class);
        $settings = SystemSetting::all()->groupBy('setting_group');

        $groups = $settings->map(fn ($items, $group) => [
            'group' => $group,
            'settings' => $items->map(fn ($item) => [
                'key' => $item->setting_key,
                'value' => $item->setting_value,
                'label' => str($item->setting_key)->replace('_', ' ')->title(),
            ]),
        ])->values();

        return Inertia::render('Admin/SystemSettings', [
            'groups' => $groups,
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
}
