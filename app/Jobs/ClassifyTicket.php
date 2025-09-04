<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\RateLimitException;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
    }

    public function handle(TicketClassifier $classifier): void
    {
        Log::info('Classifying ticket', ['id' => (string) $this->ticket->id]);

        try {
            $classifier->classify($this->ticket);
        } catch (RateLimitException $e) {
            // Re-schedule the job after the rate limit window
            $delay = max(1, $e->retryAfter);
            $this->release($delay);
        }
    }
}
