<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'middle_name' => $request->user()->middle_name,
                    'name_extension' => $request->user()->name_extension,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'profile_picture_url' => $request->user()->profile_picture_path
                        ? Storage::url($request->user()->profile_picture_path)
                        : null,
                    'aup_accepted' => $request->user()->acceptable_use_policy_accepted_at !== null,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'reference_code' => $request->session()->get('reference_code'),
            ],
            'ziggy' => fn () => (new Ziggy)->toArray(),
        ];
    }
}
