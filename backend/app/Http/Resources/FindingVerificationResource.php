<?php

namespace App\Http\Resources;

use App\Enums\AuditorConclusion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindingVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_id' => $this->finding_id,
            'round' => $this->round,
            'auditor_result' => $this->auditor_result,
            'auditor_conclusion' => $this->auditor_conclusion instanceof AuditorConclusion
                ? $this->auditor_conclusion->value
                : $this->auditor_conclusion,
            'auditor_conclusion_label' => $this->auditor_conclusion instanceof AuditorConclusion
                ? $this->auditor_conclusion->label()
                : null,
            'verified_date' => $this->verified_date?->toDateString(),
            'notes' => $this->notes,
            'is_closed' => $this->is_closed,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}