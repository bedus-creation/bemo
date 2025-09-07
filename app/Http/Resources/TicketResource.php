<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
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
            'id' => $this->id,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status->value,
            'note' => $this->note,

            'category' => TicketCategoryResource::make($this->whenLoaded('category')),
            'classification' => TicketClassificationResource::make($this->whenLoaded('classification')),

            'created_at' => DateHelper::response($this->created_at),
            'updated_at' => DateHelper::response($this->updated_at),
        ];
    }
}
