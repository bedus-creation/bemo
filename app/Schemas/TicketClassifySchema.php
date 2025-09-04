<?php

namespace App\Schemas;

class TicketClassifySchema
{
    public function __construct(
        public string $category,
        public float $confidence,
        public string $explanation,
    ) {}
}
