<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;

class VoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function view(User $user, Voucher $voucher): bool
    {
        return in_array($user->role, ['mswdo', 'accountant', 'treasurer', 'mayors_office']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'mswdo';
    }

    public function approve(User $user, Voucher $voucher): bool
    {
        return $user->role === 'accountant';
    }

    public function returnVoucher(User $user, Voucher $voucher): bool
    {
        return $user->role === 'accountant';
    }

    public function acknowledge(User $user, Voucher $voucher): bool
    {
        return $user->role === 'treasurer';
    }

    public function markReady(User $user, Voucher $voucher): bool
    {
        return $user->role === 'treasurer';
    }

    public function hold(User $user, Voucher $voucher): bool
    {
        return $user->role === 'treasurer';
    }

    public function reEvaluate(User $user, Voucher $voucher): bool
    {
        return $user->role === 'treasurer';
    }
}
