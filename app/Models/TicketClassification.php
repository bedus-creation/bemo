<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketClassification extends Model
{
    protected $table = 'tickets_classifications';

    protected $fillable = [
        'ticket_id',
        'category_id',
        'explanation',
        'confidence',
    ];
}
