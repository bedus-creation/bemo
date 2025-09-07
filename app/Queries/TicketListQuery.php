<?php

namespace App\Queries;

use App\Dtos\TicketFilterDto;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

readonly class TicketListQuery
{
    public function __construct(
        public TicketFilterDto $dto,
    ) {}

    public function getQuery(): Builder
    {
        return Ticket::query()
            ->with(['category', 'classification'])
            ->when($this->dto->status, function (Builder $query) {
                $query->where('status', $this->dto->status);
            })->when($this->dto->category, function (Builder $query) {
                $query->where('ticket_category_id', $this->dto->category);
            })->when($this->dto->q, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('subject', 'like', "%{$this->dto->q}%")
                        ->orWhere('body', 'like', "%{$this->dto->q}%");
                });
            })->orderByDesc('created_at');
    }
}
