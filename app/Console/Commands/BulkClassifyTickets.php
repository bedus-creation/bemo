<?php

namespace App\Console\Commands;

use App\Jobs\ClassifyTicketBack;
use App\Models\Ticket;
use App\Services\RateLimitException;
use Illuminate\Console\Command;

class BulkClassifyTickets extends Command
{
    protected $signature = 'tickets:bulk-classify 
                            {--status= : Filter by Ticket status} 
                            {--chunk=100 : Number of tickets to classify per batch} 
                            {--queue=default : Queue to use for job dispatching}
                            {--force=false : Force classify if it already classified}';

    protected $description = 'Classify a batch of tickets using OpenAI with per-minute rate limiting.';

    public function handle(): void
    {
        $status = (string) $this->option('status');
        $chunk  = (int) $this->option('chunk');
        $queue  = (bool) $this->option('queue');
        $force  = (bool) $this->option('force');

        Ticket::query()
            ->select('id')
            ->when($status, fn($query) => $query->where('status', $status))
            ->when(!$force, fn($query) => $query->whereDoesntHave('classification'))
            ->orderByDesc('created_at')
            ->chunkById($chunk, function ($tickets) use ($queue) {
                foreach ($tickets as $ticket) {
                    ClassifyTicketBack::dispatch($ticket->id)->onQueue($queue);
                }

                $this->info("Process upto last Ticket Id: {$tickets->last()->id}.");
            });
    }
}
