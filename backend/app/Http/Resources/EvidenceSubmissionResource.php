<?php

namespace App\Http\Resources;

use App\Enums\EvidenceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action_plan_id' => $this->action_plan_id,
            'status' => $this->status instanceof EvidenceStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof EvidenceStatus ? $this->status->label() : null,
            'submitted_by' => $this->submitted_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'revision_note' => $this->revision_note,
            'files' => $this->whenLoaded('files', fn () => EvidenceFileResource::collection($this->files)),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}