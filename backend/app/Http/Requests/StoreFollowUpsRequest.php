<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.description' => ['required', 'string'],
            'rows.*.target_date' => ['required', 'date'],
            'rows.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'rows.*.pic_ids' => ['required', 'array', 'min:1'],
            'rows.*.pic_ids.*' => ['integer', 'exists:users,id'],
            'rows.*.linked_follow_up_id' => ['nullable', 'integer', 'exists:follow_ups,id'],
        ];
    }
}