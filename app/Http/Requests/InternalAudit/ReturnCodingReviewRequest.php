<?php

namespace App\Http\Requests\InternalAudit;

use Illuminate\Foundation\Http\FormRequest;

class ReturnCodingReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remarks') && $this->remarks) {
            $this->merge(['remarks' => trim(strip_tags($this->remarks))]);
        }
    }

    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Please provide remarks explaining why the coding is being returned.',
            'remarks.min' => 'Remarks must be at least 10 characters.',
            'remarks.max' => 'Remarks must not exceed 1,000 characters.',
        ];
    }
}
