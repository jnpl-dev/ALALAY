<?php

namespace App\Rules;

use App\Models\Application;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BeneficiaryEligible implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $firstName = request('beneficiary_first_name');
        $lastName = request('beneficiary_last_name');
        $middleName = request('beneficiary_middle_name');
        $nameExtension = request('beneficiary_name_extension');

        if (!$firstName || !$lastName) {
            return;
        }

        $query = Application::where('beneficiary_first_name', $firstName)
            ->where('beneficiary_last_name', $lastName);

        if ($middleName) {
            $query->where('beneficiary_middle_name', $middleName);
        }

        if ($nameExtension) {
            $query->where('beneficiary_name_extension', $nameExtension);
        }

        $hasActive = (clone $query)
            ->whereNotIn('status', ['claimed'])
            ->exists();

        if ($hasActive) {
            $fail('This beneficiary already has an active application in progress.');
            return;
        }

        $recentlyClaimed = (clone $query)
            ->where('status', 'claimed')
            ->where('claimed_at', '>=', now()->subMonths(3))
            ->exists();

        if ($recentlyClaimed) {
            $fail('This beneficiary has already claimed assistance within the last 3 months. A new application can be submitted after the cooldown period.');
        }
    }
}
