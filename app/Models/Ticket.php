<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read string       $id
 * @property-read string       $subject
 * @property-read string       $body
 * @property-read TicketStatus $status
 * @property-read int          $ticket_category_id
 */
class Ticket extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'subject',
        'body',
        'status',
        'ticket_category_id',
    ];

    public function casts(): array
    {
        return [
            'status' => TicketStatus::class
        ];
    }

    public function classification(): HasOne
    {
        return $this->hasOne(TicketClassification::class);
    }
}
