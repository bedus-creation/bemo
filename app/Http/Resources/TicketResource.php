<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'category' => $this->category,
            'note' => $this->note,
            'classification_explanation' => $this->classification_explanation,
            'classification_confidence' => $this->classification_confidence,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
