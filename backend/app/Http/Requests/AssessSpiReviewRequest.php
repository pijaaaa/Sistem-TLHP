<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessSpiReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.follow_up_id' => ['required', 'integer', 'exists:follow_ups,id'],
            'items.*.result' => ['required', 'in:SESUAI,REVISI'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}