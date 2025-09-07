<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read string       $id
 * @property-read string       $subject
 * @property-read string       $body
 * @property-read TicketStatus $status
 * @property-read int          $ticket_category_id
 * @property-read string       $note
 * @property-read Carbon       $created_at
 * @property-read Carbon       $updated_at
 */
class Ticket extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'subject',
        'body',
        'status',
        'ticket_category_id',
        'note',
    ];

    public function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function classification(): HasOne
    {
        return $this->hasOne(TicketClassification::class);
    }
}
