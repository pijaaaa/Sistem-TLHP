<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFindingDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['required', 'integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_ids.required' => 'Pilih setidaknya satu departemen.',
            'department_ids.min' => 'Pilih setidaknya satu departemen.',
            'department_ids.*.exists' => 'Departemen yang dipilih tidak valid.',
        ];
    }
}
