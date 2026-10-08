<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_id' => $this->finding_id,
            'department_id' => $this->department_id,
            'code' => $this->code,
            'title' => $this->title,
            'condition' => $this->condition,
            'criteria' => $this->criteria,
            'cause' => $this->cause,
            'impact' => $this->impact,
            'risk' => $this->risk?->value,
            'risk_label' => $this->risk?->label(),
            'deadline' => $this->deadline?->toDateString(),
            'loss_idr' => $this->loss_idr,
            'loss_usd' => $this->loss_usd,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'current_revision' => $this->current_revision,
            'revision_label' => $this->revision_label,
            'progress' => (float) $this->progress,
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'can_edit' => $this->status->isEditable(),
            'finding' => new FindingResource($this->whenLoaded('finding')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
