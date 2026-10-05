<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*.view' => ['required', 'boolean'],
            'permissions.*.create' => ['boolean'],
            'permissions.*.update' => ['boolean'],
            'permissions.*.delete' => ['boolean'],
        ];
    }
}
