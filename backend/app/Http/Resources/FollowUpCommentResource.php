<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follow_up_id' => $this->follow_up_id,
            'author_id' => $this->author_id,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'role' => $this->author->role->value,
            ]),
            'kind' => $this->kind->value,
            'kind_label' => $this->kind->label(),
            'body' => $this->body,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}