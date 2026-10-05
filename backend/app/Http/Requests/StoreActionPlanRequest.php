<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul rencana aksi wajib diisi.',
            'weight.required' => 'Bobot wajib diisi.',
            'weight.numeric' => 'Bobot harus berupa angka.',
            'weight.min' => 'Bobot minimal 0.',
            'weight.max' => 'Bobot maksimal 100.',
        ];
    }
}
