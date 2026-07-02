<?php

namespace App\Policies;

use App\Models\AssistanceCode;
use App\Models\User;

class AssistanceCodePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function view(User $user, AssistanceCode $assistanceCode): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'aics_staff';
    }
}
