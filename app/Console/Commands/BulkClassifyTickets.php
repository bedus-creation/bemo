<?php

namespace App\Console\Commands;

use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use App\Services\RateLimitException;
use App\Services\TicketClassifier;
use Illuminate\Console\Command;

class BulkClassifyTickets extends Command
{
    protected $signature = 'tickets:bulk-classify {--status=open} {--limit=100} {--queue}';
    protected $description = 'Classify a batch of tickets using OpenAI with per-minute rate limiting.';

    public function handle(TicketClassifier $classifier): int
    {
        $status = (string) $this->option('status');
        $limit = (int) $this->option('limit');
        $queue = (bool) $this->option('queue');

        $query = Ticket::query();
        if ($status) {
            $query->where('status', $status);
        }
        // Prefer tickets without classification yet
        $query->whereNull('classification_explanation')
            ->orderByDesc('created_at')
            ->limit($limit);

        $tickets = $query->get();
        if ($tickets->isEmpty()) {
            $this->info('No tickets to classify.');
            return self::SUCCESS;
        }

        $this->info("Classifying {$tickets->count()} tickets" . ($queue ? ' (queued)' : ' (synchronously)'));

        $processed = 0;
        foreach ($tickets as $ticket) {
            if ($queue) {
                ClassifyTicket::dispatch($ticket);
                $processed++;
                continue;
            }

            try {
                $classifier->classify($ticket);
                $processed++;
            } catch (RateLimitException $e) {
                $wait = max(1, $e->retryAfter);
                $this->warn("Rate limit reached. Waiting {$wait}s...");
                sleep($wait);
                // retry once after waiting
                try {
                    $classifier->classify($ticket);
                    $processed++;
                } catch (RateLimitException $e2) {
                    $this->error('Still rate limited; stopping early.');
                    break;
                }
            }
        }

        $this->info("Processed {$processed} ticket(s).");
        return self::SUCCESS;
    }
}
