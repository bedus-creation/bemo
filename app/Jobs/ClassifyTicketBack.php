<?php

namespace App\Jobs;

use App\Services\RateLimitException;
use App\Services\TicketClassifier;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;


class ClassifyTicketBack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0; // Allowing untimed retries, since we want to do time-based

    public int $maxExceptions = 3; // Allowing 3 exceptions before we stop retrying

    public function __construct(public string $ticketId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [
            1 * 60,
            5 * 60,
            10 * 60
        ];
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            // Instead of sending unlimited requests to the API, we will limit the number of requests per minute
            new RateLimited('openai'),

            // if the same ticket is being processed, don't release'
            (new WithoutOverlapping($this->ticketId))->dontRelease(),
        ];
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(60); // We will retry for 60 minutes, as because RateLimiter may just release it back to the queue
    }

    public function handle(TicketClassifier $classifier): void
    {
        $classifier->handle($this->ticketId);
    }
}
