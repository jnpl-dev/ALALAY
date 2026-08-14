<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\UpdateAccountRequest;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function edit()
    {
        $this->authorize('update', request()->user());
        $user = request()->user();
        return Inertia::render('Auth/AccountSettings', [
            'userData' => Inertia::defer(fn () => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'name_extension' => $user->name_extension,
                'email' => $user->email,
            ]),
        ]);
    }

    public function update(UpdateAccountRequest $request)
    {
        $user = $request->user();
        $this->authorize('update', $user);
        $validated = $request->safe();
        $data = $validated->except(['current_password', 'password', 'password_confirmation', 'profile_picture']);

        if ($validated->has('password') && $validated->password) {
            $data['password'] = $validated->password;
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture_path) {
                Storage::disk('supabase')->delete($user->profile_picture_path);
            }

            $result = (new FileUploadService)->upload($request->file('profile_picture'), 'profile_pictures', $user->id, disk: 'supabase');

            $data['profile_picture_name'] = $result['file_name'];
            $data['profile_picture_path'] = $result['file_path'];
            $data['profile_picture_size'] = $result['file_size'];
            $data['profile_picture_mime_type'] = $result['mime_type'];
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    public function profilePicture()
    {
        $user = request()->user();

        if (!$user || !$user->profile_picture_path) {
            abort(404);
        }

        try {
            $image = Storage::disk('supabase')->get($user->profile_picture_path);
            $mimeType = $user->profile_picture_mime_type ?? 'image/jpeg';

            return response($image, 200, ['Content-Type' => $mimeType]);
        } catch (\Exception $e) {
            abort(404);
        }
    }
}
