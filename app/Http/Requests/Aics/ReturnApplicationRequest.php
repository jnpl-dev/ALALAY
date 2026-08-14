<?php

namespace App\Http\Requests\Aics;

use Illuminate\Foundation\Http\FormRequest;

class ReturnApplicationRequest extends FormRequest
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
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['exists:application_documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Please provide remarks explaining why the application is being returned.',
            'remarks.min' => 'Remarks must be at least 10 characters.',
            'remarks.max' => 'Remarks must not exceed 1,000 characters.',
            'document_ids.*.exists' => 'One or more selected documents are invalid.',
        ];
    }
}
