<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    protected array $allowedKeys = [
        'max_file_size_kb',
        'allowed_mime_types',
        'sms_enabled',
        'sms_template_submission_complete',
        'sms_template_under_review',
        'sms_template_resubmission_needed',
        'sms_template_cheque_ready',
        'sms_template_cheque_claiming',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('settings') && is_array($this->settings)) {
            $this->merge([
                'settings' => array_intersect_key(
                    $this->settings,
                    array_flip($this->allowedKeys)
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'No settings data provided.',
        ];
    }
}
