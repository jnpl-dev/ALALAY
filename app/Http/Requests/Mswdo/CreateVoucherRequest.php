<?php

namespace App\Http\Requests\Mswdo;

use Illuminate\Foundation\Http\FormRequest;

class CreateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voucher_file' => 'required|file|mimes:pdf|max:20480',
            'page_count' => 'required|integer|min:1|max:10',
            'adjustment_remarks' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'voucher_file.required' => 'The voucher PDF is required.',
            'voucher_file.mimes' => 'The voucher must be a PDF file.',
            'voucher_file.max' => 'The voucher must not exceed 20MB.',
        ];
    }
}
