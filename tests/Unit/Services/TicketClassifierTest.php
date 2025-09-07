<?php

namespace Tests\Unit\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\OpenAIFake;
use Tests\TestCase;

class TicketClassifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Enable the OpenAI classification feature for these tests
        config()->set('services.openai.features.classify_enabled', true);
    }

    public function test_classifies_and_inserts_when_no_category_set(): void
    {
        $billing = TicketCategory::query()->create(['name' => 'Billing', 'description' => 'Billing related']);
        TicketCategory::query()->create(['name' => 'Technical', 'description' => 'Technical issues']);

        $ticket = Ticket::factory()->create(['ticket_category_id' => null]);

        OpenAIFake::fake([
            'category' => 'Billing',
            'explanation' => 'Looks like a billing question.',
            'confidence' => 0.9,
        ]);

        // Act
        (new TicketClassifier)->handle($ticket->id);

        // Assert: Classification row created
        $this->assertDatabaseHas('tickets_classifications', [
            'ticket_id' => $ticket->id,
            'category_id' => $billing->id,
            'explanation' => 'Looks like a billing question.',
            'confidence' => 0.9,
        ]);

        // Assert: ticket updated with classified category
        $ticket->refresh();
        $this->assertSame($billing->id, $ticket->ticket_category_id);
    }

    public function test_does_not_override_existing_category_but_logs_classification(): void
    {
        // Arrange: create categories and a ticket already classified by user
        $initial = TicketCategory::query()->create(['name' => 'General', 'description' => null]);
        $other = TicketCategory::query()->create(['name' => 'Technical', 'description' => null]);

        $ticket = Ticket::factory()->create(['ticket_category_id' => $initial->id]);

        OpenAIFake::fake([
            'category' => 'Technical',
            'explanation' => 'Mentions error logs and stack traces.',
            'confidence' => 0.8,
        ]);

        // Act
        (new TicketClassifier)->handle($ticket->id);

        // Assert: ticket category NOT overridden
        $ticket->refresh();
        $this->assertSame($initial->id, $ticket->ticket_category_id);

        // But a classification record should still be stored for analytics/history
        $this->assertDatabaseHas('tickets_classifications', [
            'ticket_id' => $ticket->id,
            'category_id' => $other->id,
            'explanation' => 'Mentions error logs and stack traces.',
            'confidence' => 0.8,
        ]);
    }
}
