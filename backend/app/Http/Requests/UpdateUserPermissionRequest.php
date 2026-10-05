<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*.view' => ['boolean', 'nullable'],
            'permissions.*.create' => ['boolean', 'nullable'],
            'permissions.*.update' => ['boolean', 'nullable'],
            'permissions.*.delete' => ['boolean', 'nullable'],
        ];
    }
}
