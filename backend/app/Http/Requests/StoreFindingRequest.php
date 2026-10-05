<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:findings,code'],
            'title' => ['required', 'string', 'max:255'],
            'finding_date' => ['nullable', 'date'],
            'severity' => ['nullable', 'string', 'max:50'],
            'recommendation' => ['nullable', 'string'],
            'auditor_action_plan' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
