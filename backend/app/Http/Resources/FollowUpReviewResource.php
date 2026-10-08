<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follow_up_id' => $this->follow_up_id,
            'reviewer_id' => $this->reviewer_id,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'role' => $this->reviewer->role->value,
            ]),
            'decision' => $this->decision->value,
            'decision_label' => $this->decision->label(),
            'note' => $this->note,
            'weight_before' => (int) $this->weight_before,
            'weight_after' => (int) ($this->weight_after ?? $this->weight_before),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}