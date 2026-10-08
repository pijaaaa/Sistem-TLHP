<?php

namespace App\Http\Resources;

use App\Enums\FindingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'source' => $this->source,
            'source_name' => $this->source_name,
            'lhp_number' => $this->lhp_number,
            'lhp_date' => $this->lhp_date?->toDateString(),
            'finding_date' => $this->finding_date?->toDateString(),
            'response_period_start' => $this->response_period_start?->toDateString(),
            'response_period_end' => $this->response_period_end?->toDateString(),
            'fiscal_year' => $this->fiscal_year,
            'scope' => $this->scope,
            'title' => $this->title,
            'status' => $this->status instanceof FindingStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof FindingStatus ? $this->status->label() : null,
            'age_days' => $this->age_days,
            'progress' => (new \App\Services\ProgressService())->findingProgress($this),
            'activated_at' => $this->activated_at?->toDateTimeString(),
            'closed_at' => $this->closed_at?->toDateTimeString(),
            'closed_by' => $this->closed_by,
            'created_by' => $this->created_by,
            'documents_count' => (int) ($this->documents_count ?? 0),
            'auditee_departments' => DepartmentResource::collection($this->whenLoaded('auditee_departments')),
            'action_plans_count' => $this->whenCounted('action_plans'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
