<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->route('user')->id],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $this->route('user')->id],
            'role' => ['required', Rule::enum(Role::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id,' . $this->route('user')->id],
            'is_active' => ['boolean'],
            'password' => ['sometimes', 'string', 'confirmed', 'min:8'],
        ];
    }
}
