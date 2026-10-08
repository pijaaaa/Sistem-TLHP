<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendActionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:action_plans,id'],
        ];
    }
}
