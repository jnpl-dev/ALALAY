<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;

class VoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['mswdo', 'accountant', 'treasurer', 'budget_officer']);
    }

    public function view(User $user, Voucher $voucher): bool
    {
        return in_array($user->role, ['mswdo', 'accountant', 'treasurer', 'budget_officer']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'mswdo';
    }

    public function approve(User $user, ?Voucher $voucher): bool
    {
        return $user->role === 'accountant';
    }

    public function acknowledge(User $user, ?Voucher $voucher): bool
    {
        return $user->role === 'treasurer';
    }

    public function budgetApprove(User $user, ?Voucher $voucher): bool
    {
        return $user->role === 'budget_officer';
    }

    public function budgetHold(User $user, ?Voucher $voucher): bool
    {
        return $user->role === 'budget_officer';
    }

    public function budgetRelease(User $user, ?Voucher $voucher): bool
    {
        return $user->role === 'budget_officer';
    }
}
