<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpProgressReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follow_up_id' => $this->follow_up_id,
            'progress_value' => (int) $this->progress_value,
            'note' => $this->note,
            'reported_by' => $this->reported_by,
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ]),
            'reported_at' => $this->reported_at?->toDateTimeString(),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}