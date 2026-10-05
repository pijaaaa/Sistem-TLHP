<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = config('upload.max_size_kb');
        $mimes = implode(',', config('upload.allowed_mimes'));

        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*.file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
            'files.*.label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Minimal satu file evidence wajib diunggah.',
            'files.min' => 'Minimal satu file evidence wajib diunggah.',
            'files.*.file.required' => 'File evidence wajib diunggah.',
            'files.*.file.mimes' => 'Tipe file evidence tidak diizinkan.',
            'files.*.file.max' => 'Ukuran file evidence melebihi batas maksimum.',
        ];
    }
}