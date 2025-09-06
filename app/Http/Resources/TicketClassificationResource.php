<?php

namespace App\Http\Resources;

use App\Models\TicketClassification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketClassification
 */
class TicketClassificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'explanation' => $this->explanation,
            'confidence'  => $this->confidence,

            'category' => TicketCategoryResource::make($this->whenLoaded('category')),
        ];
    }
}
