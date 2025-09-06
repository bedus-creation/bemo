<?php

namespace App\Http\Controllers;

use App\Jobs\TicketClassifierJob;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketClassifyController extends Controller
{
    public function store(Ticket $ticket): JsonResponse
    {
        TicketClassifierJob::dispatch($ticket->id);

        return response()->json([
            'message' => 'Classification job dispatched.'
        ], 202);
    }
}
