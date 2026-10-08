<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => (int) $this->size,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
