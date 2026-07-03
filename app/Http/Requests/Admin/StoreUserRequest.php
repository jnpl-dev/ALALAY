<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'name_extension' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:admin,aics_staff,mswdo,accountant,treasurer,mayors_office'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ];
    }
}
