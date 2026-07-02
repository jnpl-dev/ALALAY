<?php

namespace App\Http\Requests\Public;

use App\Models\AssistanceCategory;
use App\Models\RequiredDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:assistance_categories,id'],
            'claimant_last_name' => ['required', 'string', 'max:255'],
            'claimant_first_name' => ['required', 'string', 'max:255'],
            'claimant_middle_name' => ['nullable', 'string', 'max:255'],
            'claimant_name_extension' => ['nullable', 'string', 'max:10'],
            'claimant_sex' => ['required', 'in:Male,Female'],
            'claimant_dob' => ['required', 'date', 'before:today'],
            'claimant_address' => ['required', 'string'],
            'claimant_phone' => ['required', 'string', 'max:20'],
            'claimant_email' => ['nullable', 'email', 'max:255'],
            'claimant_relationship_to_beneficiary' => ['required', 'string', 'max:255'],
            'beneficiary_last_name' => ['required', 'string', 'max:255'],
            'beneficiary_first_name' => ['required', 'string', 'max:255'],
            'beneficiary_middle_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_name_extension' => ['nullable', 'string', 'max:10'],
            'beneficiary_sex' => ['required', 'in:Male,Female'],
            'beneficiary_dob' => ['required', 'date', 'before:today'],
            'beneficiary_address' => ['required', 'string'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'file', 'image', 'mimes:jpeg,png', 'max:5120'],
            'document_ids' => ['required', 'array'],
            'document_ids.*' => ['required', 'exists:required_documents,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $category = AssistanceCategory::with('requiredDocuments')->find($this->category_id);
            if (!$category || !$category->is_active) {
                $validator->errors()->add('category_id', 'The selected category is not available.');
                return;
            }

            $categoryDocIds = $category->requiredDocuments->where('is_active', true)->pluck('id')->toArray();
            $submittedDocIds = $this->document_ids ?? [];

            $mandatoryDocIds = $category->requiredDocuments
                ->where('is_active', true)
                ->where('is_mandatory', true)
                ->pluck('id')
                ->toArray();

            $missingMandatory = array_diff($mandatoryDocIds, $submittedDocIds);
            if (!empty($missingMandatory)) {
                $names = RequiredDocument::whereIn('id', $missingMandatory)->pluck('doc_name')->implode(', ');
                $validator->errors()->add('documents', "The following mandatory documents are missing: {$names}.");
            }

            $invalidDocs = array_diff($submittedDocIds, $categoryDocIds);
            if (!empty($invalidDocs)) {
                $validator->errors()->add('document_ids', 'Some submitted documents do not belong to the selected category.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'documents.*.image' => 'Each document must be a valid image file (JPEG or PNG).',
            'documents.*.mimes' => 'Documents must be in JPEG or PNG format.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
        ];
    }
}
