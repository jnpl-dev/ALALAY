<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('otp_code')) {
            $this->merge([
                'otp_code' => preg_replace('/\D/', '', trim($this->otp_code)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'otp_code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_code.required' => 'Please enter the verification code.',
            'otp_code.size' => 'The verification code must be exactly 6 digits.',
        ];
    }
}
