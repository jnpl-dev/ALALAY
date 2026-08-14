<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'John Paul',
            'last_name' => 'Laureano',
            'middle_name' => null,
            'name_extension' => null,
            'email' => 'johnpaul022005123@gmail.com',
            'password' => Hash::make('12345'),
            'role' => 'admin',
            'status' => 'active',
            'is_online' => false,
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        User::create([
            'first_name' => 'Audit',
            'last_name' => 'Reviewer',
            'middle_name' => null,
            'name_extension' => null,
            'email' => 'internalaudit@example.com',
            'password' => Hash::make('12345'),
            'role' => 'internal_audit',
            'status' => 'active',
            'is_online' => false,
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        User::create([
            'first_name' => 'Budget',
            'last_name' => 'Officer',
            'middle_name' => null,
            'name_extension' => null,
            'email' => 'budgetofficer@example.com',
            'password' => Hash::make('12345'),
            'role' => 'budget_officer',
            'status' => 'active',
            'is_online' => false,
            'acceptable_use_policy_accepted_at' => now(),
        ]);
    }
}
