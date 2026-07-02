<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\UpdateAccountRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function edit()
    {
        return Inertia::render('Auth/AccountSettings');
    }

    public function update(UpdateAccountRequest $request)
    {
        $user = $request->user();
        $validated = $request->safe();
        $data = $validated->except(['current_password', 'password', 'password_confirmation', 'profile_picture']);

        if ($validated->has('password') && $validated->password) {
            $data['password'] = $validated->password;
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture_path) {
                Storage::disk('supabase')->delete($user->profile_picture_path);
            }

            $file = $request->file('profile_picture');
            $path = 'profile_pictures/' . $user->id . '/' . $file->hashName();
            Storage::disk('supabase')->put($path, file_get_contents($file->getRealPath()));

            $data['profile_picture_name'] = $file->getClientOriginalName();
            $data['profile_picture_path'] = $path;
            $data['profile_picture_size'] = $file->getSize();
            $data['profile_picture_mime_type'] = $file->getMimeType();
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

        $url = Storage::disk('supabase')->temporaryUrl(
            $user->profile_picture_path,
            now()->addMinutes(5)
        );

        return Redirect::away($url);
    }
}
