<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? new UserResource($this->user) : null),
            'ip_address' => $this->ip_address,
            'description' => $this->description,
            'payload' => $this->payload,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}