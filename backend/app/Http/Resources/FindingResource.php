<?php

namespace App\Http\Resources;

use App\Enums\AssessmentStatus;
use App\Enums\FindingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'finding_date' => $this->finding_date?->toDateString(),
            'severity' => $this->severity,
            'status' => $this->status instanceof FindingStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof FindingStatus ? $this->status->label() : null,
            'recommendation' => $this->recommendation,
            'auditor_action_plan' => $this->auditor_action_plan,
            'documents_count' => $this->documents_count ?? $this->documents()->count(),
            'current_round' => $this->current_round ?? 1,
            'assessment_status' => $this->assessment_status instanceof AssessmentStatus ? $this->assessment_status->value : $this->assessment_status,
            'assessment_status_label' => $this->assessment_status instanceof AssessmentStatus ? $this->assessment_status->label() : null,
            'assessment_note' => $this->assessment_note,
            'assessed_by' => $this->assessed_by,
            'assessed_at' => $this->assessed_at?->toDateTimeString(),
            'progress' => $this->getAttribute('progress'),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
