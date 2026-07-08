<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\SignedUrlService;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $search = request('search');
        $role = request('role');
        $status = request('status');

        $users = User::query()
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($role, fn ($q, $r) => $q->where('role', $r))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10)
            ->through(fn ($user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'name_extension' => $user->name_extension,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'profile_picture_path' => $user->profile_picture_path,
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => request()->only(['search', 'role', 'status']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);
        return Inertia::render('Admin/Users/Create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'name_extension' => $request->name_extension,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'name_extension' => $user->name_extension,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        if ($user->id === request()->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);
        if ($user->id === request()->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot toggle your own status.');
        }

        if ($user->status === 'active') {
            $user->update(['status' => 'inactive']);
        } else {
            $user->update(['status' => 'active']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User status updated.');
    }

    public function revokeSessions(User $user)
    {
        $this->authorize('revokeSessions', $user);
        // Delete all sessions for this user except current one
        \DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', request()->session()->getId())
            ->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Sessions revoked.');
    }

    public function profilePicture(User $user)
    {
        $this->authorize('view', $user);
        if (!$user->profile_picture_path) {
            abort(404);
        }

        $url = (new SignedUrlService)->generate($user->profile_picture_path, 5);

        if (!$url) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Redirect::away($url);
    }
}
