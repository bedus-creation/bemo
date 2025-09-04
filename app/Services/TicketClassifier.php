<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Schemas\TicketClassifySchema;
use OpenAI\Laravel\Facades\OpenAI;

class TicketClassifier
{
    public function handle(string $ticketId): void
    {
        $ticket = Ticket::query()->findOrFail($ticketId);

        $ticketCategory = $this->classifyTicket($ticket);

        $ticketCategoryId = TicketCategory::query()
            ->where('name', $ticketCategory->category)
            ->first()
            ->id;

        $ticket->classification()->create([
            'category'    => $ticketCategoryId,
            'explanation' => $ticketCategory->explanation,
            'confidence'  => $ticketCategory->confidence,
        ]);

        // If the ticket has already had a category, skip
        if ($ticket->ticket_category_id) {
            return;
        }

        $ticket->update(['ticket_category_id' => $ticketCategoryId]);
    }

    public function classifyTicket(Ticket $ticket): TicketClassifySchema
    {
        $categories = TicketCategory::query()->select('name')->get();

        $instruction = 'You are a support ticket classifier. Respond ONLY with strict JSON having keys: '.
                       '"category" (string), "explanation" (string), and "confidence" (number between 0 and 1). ';

        $response = OpenAI::responses()
            ->create([
                'model'        => 'gpt-5',
                'instructions' => $instruction,
                'input'        => <<<TICKET
The ticket is given as follows:
subject: {$ticket->subject}
description: {$ticket->body}
TICKET
                ,
                'text'         => [
                    'format' => [
                        'type'   => 'json_schema',
                        'name'   => 'ticket_classification_schema',
                        'schema' => [
                            'type'                 => 'object',
                            'properties'           => [
                                'category'    => [
                                    'type' => 'string',
                                    'enum' => $categories->pluck('name')->toArray(),
                                ],
                                'explanation' => [
                                    'type' => 'string'
                                ],
                                'confidence'  => [
                                    'type' => 'number',
                                ]
                            ],
                            'required'             => ['category', 'explanation', 'confidence'],
                            'additionalProperties' => false,
                        ],
                    ]
                ]
            ]);

        return new TicketClassifySchema(...json_decode($response->outputText, true));
    }
}
