<?php

namespace App\Http\Controllers;

use App\Jobs\ClassifyTicketJob;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketClassifyController extends Controller
{
    public function __invoke(Ticket $ticket): JsonResponse
    {
        ClassifyTicketJob::dispatch($ticket->id);

        return response()->json([
            'message' => 'Classification job dispatched.'
        ], 202);
    }
}
