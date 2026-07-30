<?php

namespace App\Http\Requests\Aics;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssistanceCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => str_replace(',', '', $this->amount ?? ''),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'assistance_code_reference_id' => [
                'required',
                'uuid',
                'exists:assistance_code_references,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assistance_code_reference_id.required' => 'Please select an assistance code type.',
            'assistance_code_reference_id.exists' => 'The selected assistance code type is not available.',
            'amount.required' => 'Please enter the assistance amount.',
            'amount.numeric' => 'Assistance amount must be a valid number.',
            'amount.min' => 'Assistance amount must be greater than zero.',
            'amount.max' => 'Assistance amount must not exceed ₱999,999.99.',
            'amount.regex' => 'Assistance amount must have at most 2 decimal places.',
        ];
    }
}
