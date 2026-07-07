<?php

namespace App\Http\Requests\Mswdo;

use Illuminate\Foundation\Http\FormRequest;

class ApproveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'social_case_study' => 'required|file|mimes:pdf|max:20480',
            'page_count' => 'required|integer|min:1|max:20',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'social_case_study.required' => 'The social case study PDF is required.',
            'social_case_study.mimes' => 'The social case study must be a PDF file.',
            'social_case_study.max' => 'The social case study must not exceed 20MB.',
            'page_count.required' => 'Page count is required.',
            'page_count.min' => 'Page count must be at least 1.',
            'page_count.max' => 'Page count must not exceed 20.',
        ];
    }
}
