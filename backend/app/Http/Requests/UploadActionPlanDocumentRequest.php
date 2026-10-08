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
        $mimes = implode(',', config('upload.allowed_mimes', ['pdf', 'docx', 'png', 'jpg']));
        $max = config('upload.max_size_kb', 10240);

        return [
            'document' => ['required', 'file', 'mimes:' . $mimes, 'max:' . $max],
            'label' => ['required', 'string', 'max:100'],
        ];
    }
}
