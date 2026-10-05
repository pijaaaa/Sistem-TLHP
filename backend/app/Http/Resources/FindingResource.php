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
            'code' => $this->code,
            'title' => $this->title,
            'finding_date' => $this->finding_date?->toDateString(),
            'severity' => $this->severity,
            'status' => $this->status instanceof FindingStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof FindingStatus ? $this->status->label() : null,
            'recommendation' => $this->recommendation,
            'auditor_action_plan' => $this->auditor_action_plan,
            'documents_count' => $this->documents_count ?? $this->documents()->count(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
