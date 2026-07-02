<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => null,
            'name_extension' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'aics_staff',
            'status' => 'active',
            'is_online' => false,
            'profile_picture_name' => null,
            'profile_picture_path' => null,
            'profile_picture_size' => null,
            'profile_picture_mime_type' => null,
            'acceptable_use_policy_accepted_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function aicsStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'aics_staff',
        ]);
    }

    public function mswdo(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mswdo',
        ]);
    }

    public function accountant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'accountant',
        ]);
    }

    public function treasurer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'treasurer',
        ]);
    }

    public function mayorsOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mayors_office',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
