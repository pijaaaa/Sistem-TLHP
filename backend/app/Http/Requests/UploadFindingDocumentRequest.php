<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFindingDocumentRequest extends FormRequest
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
            'document' => ['required', 'array', 'min:1', 'max:10'],
            'document.*.file' => ['required', 'file', 'mimes:' . $mimes, 'max:' . $max],
            'document.*.label' => ['required', 'string', 'max:100'],
        ];
    }
}
