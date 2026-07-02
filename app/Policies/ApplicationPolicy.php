<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function view(User $user, Application $application): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function documentUrl(User $user, Application $application): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo']);
    }

    public function approve(User $user, Application $application): bool
    {
        if ($user->role === 'aics_staff' && $application->status === 'submitted') {
            return true;
        }

        if ($user->role === 'mswdo' && $application->status === 'mswdo_review') {
            return true;
        }

        return false;
    }

    public function returnApp(User $user, Application $application): bool
    {
        if ($user->role === 'aics_staff' && $application->status === 'submitted') {
            return true;
        }

        if ($user->role === 'mswdo' && $application->status === 'mswdo_review') {
            return true;
        }

        return false;
    }
}
