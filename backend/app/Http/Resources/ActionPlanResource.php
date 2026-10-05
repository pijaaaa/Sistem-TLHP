<?php

namespace App\Http\Resources;

use App\Enums\ActionPlanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_department_id' => $this->finding_department_id,
            'finding_department' => $this->whenLoaded('findingDepartment', fn () => new FindingDepartmentResource($this->findingDepartment)),
            'title' => $this->title,
            'description' => $this->description,
            'weight' => $this->weight,
            'status' => $this->status instanceof ActionPlanStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof ActionPlanStatus ? $this->status->label() : null,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver', fn () => new UserResource($this->approver)),
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'due_date' => $this->due_date?->toDateString(),
            'documents_count' => $this->documents_count ?? $this->documents()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
