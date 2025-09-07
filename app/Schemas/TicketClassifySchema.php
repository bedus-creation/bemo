<?php

namespace App\Schemas;

// TODO:
// This schema can be replaced with Json Schema Builder so we can ask for structure responses
class TicketClassifySchema
{
    public function __construct(
        public string $category,
        public float $confidence,
        public string $explanation,
    ) {}
}
