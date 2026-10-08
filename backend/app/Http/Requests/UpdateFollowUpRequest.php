<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'required', 'string'],
            'target_date' => ['sometimes', 'required', 'date'],
            'weight' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'pic_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'pic_ids.*' => ['integer', 'exists:users,id'],
            'linked_follow_up_id' => ['nullable', 'integer', 'exists:follow_ups,id'],
        ];
    }
}