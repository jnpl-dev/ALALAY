<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        return $this->json()->all() ?: $this->all();
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
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'name_extension' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
            'profile_picture' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];

        if ($this->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password to set a new password.',
            'current_password.current_password' => 'Current password is incorrect.',
            'profile_picture.max' => 'Profile picture must not exceed 2MB.',
        ];
    }
}
