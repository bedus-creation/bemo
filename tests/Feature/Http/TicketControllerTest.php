<?php

namespace Tests\Feature\Http;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketClassification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    public function test_index_returns_paginated_collection_with_expected_structure(): void
    {
        Ticket::factory()->count(15)->create();

        $response = $this->getJson(route('api.tickets.index', [
            'page'     => 1,
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'data'  => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'status',
                        'note',
                        'category',
                        'classification',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta'  => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ],
            ])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1);

        // ensure exactly 10 items returned on the first page
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_applies_filters_status_query_category(): void
    {
        $category1 = TicketCategory::query()->create(['name' => 'Billing', 'description' => 'Billing related']);
        $category2 = TicketCategory::query()->create(['name' => 'Support', 'description' => 'Support related']);

        // matching: open status, has keyword, and category1
        Ticket::factory()->create([
            'subject'            => 'Payment failed at checkout',
            'body'               => 'The payment gateway returned an error.',
            'status'             => 'open',
            'ticket_category_id' => $category1->id,
        ]);

        // non-matching by status
        Ticket::factory()->create([
            'subject'            => 'Payment failed again',
            'body'               => 'But this is closed',
            'status'             => 'closed',
            'ticket_category_id' => $category1->id,
        ]);

        // non-matching by query
        Ticket::factory()->create([
            'subject'            => 'Unrelated subject',
            'body'               => 'No keyword here',
            'status'             => 'open',
            'ticket_category_id' => $category1->id,
        ]);

        // non-matching by category
        Ticket::factory()->create([
            'subject'            => 'Payment failed sometimes',
            'body'               => 'Has keyword but wrong category',
            'status'             => 'open',
            'ticket_category_id' => $category2->id,
        ]);

        $response = $this->getJson(route('api.tickets.index', [
            'status'   => 'open',
            'query'    => 'payment',
            'category' => $category1->id,
            'per_page' => 50,
        ]));

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('open', $data[0]['status']);
        $this->assertNotNull($data[0]['category']);
        $this->assertSame($category1->id, $data[0]['category']['id']);
        $this->assertMatchesRegularExpression('/payment/i', $data[0]['subject'].' '.$data[0]['body']);
    }

    public function test_store_creates_ticket_and_returns_201_with_resource(): void
    {
        $payload = [
            'subject' => 'Cannot login to my account',
            'body'    => 'I forgot my password and reset link is not working.',
        ];

        $response = $this->postJson(route('api.tickets.store'), $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'note',
                    'category',
                    'classification',
                    'created_at',
                    'updated_at'
                ],
            ])
            ->assertJsonPath('data.subject', $payload['subject'])
            ->assertJsonPath('data.body', $payload['body'])
            ->assertJsonPath('data.status', 'open'); // default

        $this->assertDatabaseHas('tickets', [
            'subject' => $payload['subject'],
            'body'    => $payload['body'],
            'status'  => 'open',
        ]);
    }

    public function test_show_returns_ticket_with_loaded_relations(): void
    {
        $category = TicketCategory::query()->create(['name' => 'Technical', 'description' => 'Tech issues']);

        $ticket = Ticket::factory()->create([
            'ticket_category_id' => $category->id,
            'status'             => 'pending',
        ]);

        TicketClassification::query()->create([
            'ticket_id'   => $ticket->id,
            'category_id' => $category->id,
            'explanation' => 'Likely a technical issue based on keywords',
            'confidence'  => 92,
        ]);

        $response = $this->getJson(route('api.tickets.show', $ticket->id));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'note',
                    'created_at',
                    'updated_at',
                    'category'       => ['id', 'name'],
                    'classification' => [
                        'id',
                        'explanation',
                        'confidence',
                        'category' => ['id', 'name'],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.category.id', $category->id);
    }

    public function test_update_updates_ticket_fields_and_returns_resource(): void
    {
        $category = TicketCategory::query()->create(['name' => 'General', 'description' => 'General inquiries']);

        $ticket   = Ticket::factory()->create([
            'status'             => 'open',
            'note'               => null,
            'ticket_category_id' => null,
        ]);

        $payload = [
            'status'   => 'pending',
            'category' => $category->id,
            'note'     => 'We are looking into this.',
        ];

        $response = $this->putJson(route('api.tickets.update', $ticket->id), $payload);

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.note', 'We are looking into this.')
            ->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('tickets', [
            'id'                 => $ticket->id,
            'status'             => 'pending',
            'ticket_category_id' => $category->id,
            'note'               => 'We are looking into this.',
        ]);
    }
}
