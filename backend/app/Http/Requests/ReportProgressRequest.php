<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mimes = implode(',', config('upload.allowed_mimes', ['pdf', 'docx', 'png', 'jpg']));
        $max = config('upload.max_size_kb', 10240);

        return [
            'progress_value' => ['required', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'array', 'max:5'],
            'document.*.file' => ['required', 'file', 'mimes:' . $mimes, 'max:' . $max],
            'document.*.label' => ['required', 'string', 'max:100'],
        ];
    }
}