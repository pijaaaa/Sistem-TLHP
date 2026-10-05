<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pic_ids' => ['required', 'array', 'min:1'],
            'pic_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pic_ids.required' => 'Pilih setidaknya satu PIC.',
            'pic_ids.min' => 'Pilih setidaknya satu PIC.',
            'pic_ids.*.exists' => 'Pengguna yang dipilih tidak valid.',
        ];
    }
}
