<?php

namespace App\Http\Requests;

use App\Enums\ExternalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordExternalStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ExternalStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'new_deadline' => ['nullable', 'date'],
            'document' => ['required', 'array', 'min:1', 'max:5'],
            'document.*.file' => ['required', 'file', 'mimes:' . $mimes, 'max:' . $max],
            'document.*.label' => ['required', 'string', 'max:100'],
            'action_plan_ids' => ['nullable', 'array'],
            'action_plan_ids.*' => ['integer', 'exists:action_plans,id'],
        ];
    }
}