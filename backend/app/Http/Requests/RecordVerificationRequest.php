<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auditor_conclusion' => ['required', 'in:ditutup,perlu_perbaikan'],
            'auditor_result' => ['nullable', 'string'],
            'verified_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'auditor_conclusion.required' => 'Kesimpulan auditor wajib diisi.',
            'auditor_conclusion.in' => 'Kesimpulan auditor tidak valid.',
        ];
    }
}