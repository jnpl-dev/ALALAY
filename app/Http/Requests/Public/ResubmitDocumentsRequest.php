<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('document_ids') && is_array($this->document_ids)) {
            $this->merge([
                'document_ids' => array_filter($this->document_ids, fn ($id) => is_string($id) && strlen(trim($id)) === 36),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'document_ids' => ['required', 'array'],
            'document_ids.*' => ['required', 'string', 'exists:application_documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'documents.required' => 'Please upload at least one document.',
            'documents.*.required' => 'Each document must be a file.',
            'documents.*.mimes' => 'Documents must be in PDF format.',
            'documents.*.max' => 'Each document must not exceed 10MB.',
            'document_ids.required' => 'Document references are missing.',
            'document_ids.*.exists' => 'One or more referenced documents do not exist.',
        ];
    }
}
