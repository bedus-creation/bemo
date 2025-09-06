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

        $ticketCategorySchema = $this->classifyTicket($ticket);

        $ticketCategory = TicketCategory::query()
            ->where('name', $ticketCategorySchema->category)
            ->first();

        if (!$ticketCategory) {
            throw new \Exception("Ticket category not found: {$ticketCategorySchema->category}");
        }

        $ticket->classification()->create([
            'category_id' => $ticketCategory->id,
            'explanation' => $ticketCategorySchema->explanation,
            'confidence'  => $ticketCategorySchema->confidence,
        ]);

        // If the ticket has already had a category, skip
        if ($ticket->ticket_category_id) {
            return;
        }

        $ticket->update(['ticket_category_id' => $ticketCategory->id]);
    }

    public function classifyTicket(Ticket $ticket): TicketClassifySchema
    {
        if (!config('services.openai.features.classify_enabled')) {
            return new TicketClassifySchema(
                category: TicketCategory::query()->inRandomOrder()->firstOrFail()->name,
                confidence: rand(1, 10) * 0.1, // Random confidence between 0.1 and 1.0
                explanation: 'Random classification generated as AI is disabled.',
            );
        }

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

        /** @var array{'category': string, 'confidence': float, 'explanation': string} $schemeArray */
        $schemeArray = json_decode($response->outputText ?? '', true);

        return new TicketClassifySchema(...$schemeArray);
    }
}
