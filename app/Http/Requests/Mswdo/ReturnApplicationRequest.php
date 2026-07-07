<?php

namespace App\Http\Requests\Mswdo;

use Illuminate\Foundation\Http\FormRequest;

class ReturnApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remarks' => 'required|string|max:1000',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:application_documents,id',
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Please provide a reason for returning the application.',
        ];
    }
}
