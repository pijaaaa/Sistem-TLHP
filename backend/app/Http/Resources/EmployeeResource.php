<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nik' => $this->nik,
            'name' => $this->name,
            'position' => $this->position,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'role' => $this->whenLoaded('user', fn () => $this->user?->role?->value),
            'role_label' => $this->whenLoaded('user', fn () => $this->user?->role?->label()),
            'username' => $this->whenLoaded('user', fn () => $this->user?->username),
            'email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
