<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_status' => ['required', 'in:ssr,bsr,belum_ditindaklanjuti,tidak_dapat_ditindaklanjuti'],
            'note' => ['nullable', 'string'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'assessment_status.required' => 'Status assessment wajib diisi.',
            'assessment_status.in' => 'Status assessment tidak valid.',
        ];
    }
}