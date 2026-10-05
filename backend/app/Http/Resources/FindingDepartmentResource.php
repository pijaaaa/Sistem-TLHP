<?php

namespace App\Http\Resources;

use App\Enums\FindingDepartmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindingDepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_id' => $this->finding_id,
            'finding' => $this->whenLoaded('finding', fn () => new FindingResource($this->finding)),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => new DepartmentResource($this->department)),
            'status' => $this->status instanceof FindingDepartmentStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof FindingDepartmentStatus ? $this->status->label() : null,
            'assigned_by' => $this->assigned_by,
            'progress' => \App\Services\ActionPlanService::departmentProgress($this->id),
            'pics' => $this->whenLoaded('pics', function () {
                return $this->pics->map(function ($pic) {
                    return [
                        'id' => $pic->id,
                        'name' => $pic->name,
                        'email' => $pic->email,
                        'username' => $pic->username,
                        'role' => $pic->role instanceof \App\Enums\Role ? $pic->role->value : $pic->role,
                        'role_label' => $pic->role instanceof \App\Enums\Role ? $pic->role->label() : null,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
