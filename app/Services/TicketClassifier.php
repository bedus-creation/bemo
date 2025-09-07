<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Schemas\TicketClassifySchema;
use Illuminate\JsonSchema\JsonSchema;

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

        $categories = TicketCategory::query()
            ->select('name')
            ->pluck('name')
            ->toArray();

        $response = OpenAIClient::createStructureResponse(
            instruction: view('prompts.instruction')->toHtml(),
            input: view('prompts.input', ['ticket' => $ticket])->toHtml(),
            schema: JsonSchema::object(properties: [
                'category'    => JsonSchema::string()->enum($categories)->required(),
                'explanation' => JsonSchema::string()->required(),
                'confidence'  => JsonSchema::number()->required(),
            ]),
        );

        /** @var array{'category': string, 'confidence': float, 'explanation': string} $schemeArray */
        $schemeArray = json_decode($response->outputText ?? '', true);

        return new TicketClassifySchema(...$schemeArray);
    }
}
