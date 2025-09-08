<?php

namespace Tests\Feature\Http;

use App\Jobs\TicketClassifierJob;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketClassifyControllerTest extends TestCase
{
    public function test_dispatches_classification_job_and_returns_202_json(): void
    {
        Queue::fake();

        $ticket = Ticket::factory()->create();

        // Act
        $response = $this->postJson(route('api.tickets.classify.store', $ticket->id));

        // Assert response
        $response->assertStatus(202)
            ->assertExactJson([
                'message' => 'Classification job dispatched.',
            ]);

        // Assert a job dispatched with correct ticket id
        Queue::assertPushed(TicketClassifierJob::class, function (TicketClassifierJob $job) use ($ticket): bool {
            return $job->ticketId === $ticket->id;
        });
    }

    public function test_returns_404_for_missing_ticket(): void
    {
        Queue::fake();

        $nonExistentId = '01JABCDETESTIDNOTFOUND0000';

        $response = $this->postJson(route('api.tickets.classify.store', $nonExistentId));

        $response->assertStatus(404);

        // Ensure no job dispatched when ticket not found
        Queue::assertNotPushed(TicketClassifierJob::class);
    }
}
