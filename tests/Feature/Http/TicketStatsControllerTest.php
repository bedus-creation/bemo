<?php

namespace Tests\Feature\Http;

use App\Models\Ticket;
use App\Models\TicketCategory;
use Tests\TestCase;

class TicketStatsControllerTest extends TestCase
{
    public function test_ticket_stats_returns_expected_totals_and_datasets(): void
    {
        $billing = TicketCategory::create(['name' => 'Billing', 'description' => 'Billing related']);
        $support = TicketCategory::create(['name' => 'Support', 'description' => 'Support related']);

        // Seed tickets per category and status
        // Billing: 2 open, 1 pending, 0 closed
        Ticket::factory()->count(2)->create(['status' => 'open', 'ticket_category_id' => $billing->id]);
        Ticket::factory()->count(1)->create(['status' => 'pending', 'ticket_category_id' => $billing->id]);

        // Support: 1 open, 0 pending, 3 closed
        Ticket::factory()->count(1)->create(['status' => 'open', 'ticket_category_id' => $support->id]);
        Ticket::factory()->count(3)->create(['status' => 'closed', 'ticket_category_id' => $support->id]);

        // Also include uncategorized tickets: should be counted in totals/status but not in chartData buckets
        Ticket::factory()->count(2)->create(['status' => 'open', 'ticket_category_id' => null]);

        $response = $this->getJson(route('api.tickets.stats.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'total',
                'status'    => ['open', 'pending', 'closed'],
                'chartData' => [
                    'labels',
                    'datasets' => [
                        ['label', 'data', 'backgroundColor'],
                    ],
                ],
            ]);

        $json = $response->json();

        // Totals
        $this->assertSame(2 + 1 + 1 + 3 + 2, $json['total']);  // all created
        $this->assertSame(2 + 1 + 2, $json['status']['open']); // billing 2 + support 1 + uncategorized 2 -> 5
        $this->assertSame(1, $json['status']['pending']);
        $this->assertSame(3, $json['status']['closed']);

        // Chart labels in order of categories created
        $this->assertSame(['Billing', 'Support'], $json['chartData']['labels']);

        // Datasets: one per status in order [Open, Pending, Closed]
        $datasets = $json['chartData']['datasets'];
        $this->assertCount(3, $datasets);

        // Helper to find a dataset by label
        $byLabel = fn(string $label) => collect($datasets)->firstWhere('label', $label);

        $openDs    = $byLabel('Open');
        $pendingDs = $byLabel('Pending');
        $closedDs  = $byLabel('Closed');

        $this->assertNotNull($openDs);
        $this->assertNotNull($pendingDs);
        $this->assertNotNull($closedDs);

        // Data arrays align with labels [Billing, Support]
        $this->assertSame([2, 1], $openDs['data']);
        $this->assertSame([1, 0], $pendingDs['data']);
        $this->assertSame([0, 3], $closedDs['data']);

        // Colors should be hex strings
        $this->assertMatchesRegularExpression('/^#([A-Fa-f0-9]{6})$/', $openDs['backgroundColor']);
        $this->assertMatchesRegularExpression('/^#([A-Fa-f0-9]{6})$/', $pendingDs['backgroundColor']);
        $this->assertMatchesRegularExpression('/^#([A-Fa-f0-9]{6})$/', $closedDs['backgroundColor']);
    }
}
