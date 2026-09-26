<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'John Paul',
                'last_name' => 'Laureano',
                'middle_name' => null,
                'name_extension' => null,
                'email' => 'johnpaullaureano.neust@gmail.com',
                'password' => Hash::make('12345'),
                'role' => 'admin',
            ],
            [
                'first_name' => 'Audit',
                'last_name' => 'Reviewer',
                'middle_name' => null,
                'name_extension' => null,
                'email' => 'internalaudit@example.com',
                'password' => Hash::make('12345'),
                'role' => 'internal_audit',
            ],
            [
                'first_name' => 'Budget',
                'last_name' => 'Officer',
                'middle_name' => null,
                'name_extension' => null,
                'email' => 'budgetofficer@example.com',
                'password' => Hash::make('12345'),
                'role' => 'budget_officer',
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'middle_name' => $user['middle_name'],
                    'name_extension' => $user['name_extension'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                    'status' => 'active',
                    'is_online' => false,
                    'acceptable_use_policy_accepted_at' => now(),
                ]
            );
        }
    }
}
