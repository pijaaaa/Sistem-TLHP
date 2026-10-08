<?php

namespace App\Http\Resources;

use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role instanceof Role ? $this->role->value : $this->role,
            'role_label' => $this->role instanceof Role ? $this->role->label() : null,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'is_active' => $this->is_active,
            'active_until' => $this->active_until?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
