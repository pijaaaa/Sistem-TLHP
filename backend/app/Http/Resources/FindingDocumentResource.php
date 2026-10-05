<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindingDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_id' => $this->finding_id,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'label' => $this->label,
            'download_url' => route('findings.documents.download', $this->id),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
