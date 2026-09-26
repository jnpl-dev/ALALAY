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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name'  => preg_replace('/\s+/', ' ', trim(strip_tags($this->first_name ?? ''))),
            'last_name'   => preg_replace('/\s+/', ' ', trim(strip_tags($this->last_name ?? ''))),
            'middle_name' => $this->middle_name
                ? preg_replace('/\s+/', ' ', trim(strip_tags($this->middle_name)))
                : null,
            'name_extension' => $this->name_extension ?: null,
            'email'       => strtolower(trim($this->email ?? '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-zÑñ\s\-\'\.]+$/'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-zÑñ\s\-\'\.]+$/'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-zÑñ\s\-\'\.]*$/'],
            'name_extension' => ['nullable', 'string', 'max:10', 'regex:/^(Jr\.|Sr\.|III|IV|V)?$/i'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:admin,aics_staff,mswdo,accountant,treasurer,internal_audit,budget_officer'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'first_name.max' => 'First name must not exceed 100 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.max' => 'Last name must not exceed 100 characters.',
            'middle_name.regex' => 'Middle name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'name_extension.regex' => 'Name extension must be Jr., Sr., III, IV, or V.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'role.required' => 'Role is required.',
            'role.in' => 'Please select a valid role.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 12 characters.',
        ];
    }
}
