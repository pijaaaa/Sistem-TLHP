<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evidence_submission_id' => $this->evidence_submission_id,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'label' => $this->label,
            'download_url' => $this->downloadUrl(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}