<?php

namespace App\Dtos;

readonly class TicketFilterDto
{
    public function __construct(
        public ?string $status,
        public ?int $category,
        public ?string $q,
    ) {}
}
