<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case PENDING = 'pending';
    case CLOSED = 'closed';

    public function color(): string
    {
        return match ($this) {
            self::OPEN => '#1E90FF',
            self::CLOSED => '#2ECC71',
            self::PENDING => '#E67E22',
        };
    }
}
