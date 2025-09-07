<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int    $id
 * @property-read int    $ticket_id
 * @property-read int    $category_id
 * @property-read string $explanation
 * @property-read int    $confidence
 */
class TicketClassification extends Model
{
    protected $table = 'tickets_classifications';

    protected $fillable = [
        'ticket_id',
        'category_id',
        'explanation',
        'confidence',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }
}
