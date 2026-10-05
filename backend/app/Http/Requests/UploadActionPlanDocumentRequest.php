<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadActionPlanDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:' . implode(',', config('upload.allowed_mimes')), 'max:' . (config('upload.max_size_kb') * 1024)],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'File dokumen wajib diunggah.',
            'document.file' => 'File tidak valid.',
            'document.mimes' => 'Tipe file tidak diizinkan.',
            'document.max' => 'Ukuran file melebihi batas maksimum.',
        ];
    }
}
