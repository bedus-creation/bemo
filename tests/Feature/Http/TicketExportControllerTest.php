<?php

namespace Tests\Feature\Http;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketClassification;
use Tests\TestCase;

class TicketExportControllerTest extends TestCase
{
    public function test_ticket_export_streams_csv_with_headers_and_applies_filters(): void
    {
        $billing = TicketCategory::query()->create(['name' => 'Billing', 'description' => 'Billing related']);
        $support = TicketCategory::query()->create(['name' => 'Support', 'description' => 'Support related']);

        // Matching ticket (open + query + category)
        $match = Ticket::factory()->create([
            'subject'            => 'Payment failed at checkout',
            'body'               => 'Gateway error occurred',
            'status'             => 'open',
            'ticket_category_id' => $billing->id,
        ]);

        TicketClassification::query()->create([
            'ticket_id'   => $match->id,
            'category_id' => $billing->id,
            'explanation' => 'Billing related keywords',
            'confidence'  => 88,
        ]);

        // Non-matching by status
        Ticket::factory()->create([
            'subject'            => 'Payment failed different',
            'body'               => 'But this one is closed',
            'status'             => 'closed',
            'ticket_category_id' => $billing->id,
        ]);

        // Non-matching by query
        Ticket::factory()->create([
            'subject'            => 'Unrelated subject',
            'body'               => 'No keyword here',
            'status'             => 'open',
            'ticket_category_id' => $billing->id,
        ]);

        // Non-matching by category
        Ticket::factory()->create([
            'subject'            => 'Payment issue but support',
            'body'               => 'Has keyword but wrong category',
            'status'             => 'open',
            'ticket_category_id' => $support->id,
        ]);

        $response = $this->get('/api/tickets/export?status=open&query=payment&category='.$billing->id);

        $response->assertOk();
        // Content-Type can include charset; ensure it contains text/csv
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('-tickets.csv', $response->headers->get('Content-Disposition'));

        // Streamed responses: use streamedContent() helper if available, otherwise fall back to getContent()
        $content = $response->streamedContent();

        $this->assertIsString($content);

        // The first line should be CSV headers
        $lines = preg_split("/\r?\n/", trim($content));
        $this->assertNotEmpty($lines);
        $headerColumns = str_getcsv($lines[0]);
        $this->assertSame([
            'Id',
            'Subject',
            'Body',
            'Status',
            'Note',
            'Category',
            'AI Category',
            'AI Explanation',
            'AI Confidence',
            'Created Date',
            'Updated Date'
        ], $headerColumns);

        // Only one matching ticket row should be present
        $this->assertCount(2, $lines); // header + 1 row

        $row = $lines[1];
        $this->assertStringContainsString($match->id, $row);
        $this->assertStringContainsString('Payment failed at checkout', $row);
        $this->assertStringContainsString('open', $row);
        $this->assertStringContainsString('Billing', $row);                  // category name
        $this->assertStringContainsString('Billing related keywords', $row); // AI explanation
        $this->assertStringContainsString('88', $row);                       // AI confidence
    }
}
