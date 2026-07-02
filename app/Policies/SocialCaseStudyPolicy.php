<?php

namespace App\Policies;

use App\Models\SocialCaseStudy;
use App\Models\User;

class SocialCaseStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo']);
    }

    public function view(User $user, SocialCaseStudy $socialCaseStudy): bool
    {
        return in_array($user->role, ['aics_staff', 'mswdo']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'mswdo';
    }
}
