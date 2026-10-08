<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action_plan_id' => $this->action_plan_id,
            'revision_no' => $this->revision_no,
            'description' => $this->description,
            'target_date' => $this->target_date?->toDateString(),
            'weight' => (int) $this->weight,
            'progress' => (int) $this->progress,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'linked_follow_up_id' => $this->linked_follow_up_id,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'action_plan' => new ActionPlanResource($this->whenLoaded('actionPlan')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}