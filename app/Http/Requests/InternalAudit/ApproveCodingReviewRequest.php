<?php

namespace App\Http\Requests\InternalAudit;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCodingReviewRequest extends FormRequest
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
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.max' => 'Remarks must not exceed 1,000 characters.',
        ];
    }
}
