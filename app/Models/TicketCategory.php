<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int    $id
 * @property-read string $name
 * @property-read string $description
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
class TicketCategory extends Model
{
    protected $table = 'ticket_categories';

    protected $fillable = [
        'name',
        'description',
    ];
}
