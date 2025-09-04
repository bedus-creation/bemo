<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;

class TicketClassifier
{
    /**
     * Classify a ticket and persist results on the ticket.
     * Returns an array with keys: category, explanation, confidence (float 0..1).
     * May throw a RateLimitException to indicate caller should retry later.
     */
    public function classify(Ticket $ticket): array
    {
        // Global rate limit per minute
        $maxPerMinute = (int) env('OPENAI_CLASSIFY_RPM', 60);
        $rateKey = 'ticket-classifier:openai:minute';

        if (RateLimiter::tooManyAttempts($rateKey, $maxPerMinute)) {
            $retryAfter = RateLimiter::availableIn($rateKey) ?: 60;
            throw new RateLimitException('OpenAI classification rate limit reached', $retryAfter);
        }

        // Feature flag to disable external calls
        $enabled = filter_var(env('OPENAI_CLASSIFY_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

        if (!$enabled) {
            // Still mark an attempt so rate limiter roughly tracks command usage
            RateLimiter::hit($rateKey, 60);
            $result = $this->dummyResult($ticket);
            $this->storeResult($ticket, $result);
            return $result;
        }

        // Hit limiter right before external call
        RateLimiter::hit($rateKey, 60);

        try {
            $model = env('OPENAI_CLASSIFY_MODEL', 'gpt-4o-mini');
            $system = $this->systemPrompt();
            $user = $this->userPrompt($ticket);

            // Using chat.completions API via openai-php/laravel v0.11
            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '';
            $parsed = $this->parseJson($content);

            // Normalize and validate
            $result = [
                'category' => (string) ($parsed['category'] ?? 'general'),
                'explanation' => (string) ($parsed['explanation'] ?? 'No explanation provided.'),
                'confidence' => isset($parsed['confidence']) ? (float) $parsed['confidence'] : 0.5,
            ];

            $this->storeResult($ticket, $result);
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Ticket classification failed, falling back to dummy.', [
                'ticket_id' => (string) $ticket->id,
                'error' => $e->getMessage(),
            ]);
            $result = $this->dummyResult($ticket);
            $this->storeResult($ticket, $result);
            return $result;
        }
    }

    protected function systemPrompt(): string
    {
        return 'You are a support ticket classifier. Respond ONLY with strict JSON having keys: ' .
            '"category" (string), "explanation" (string), and "confidence" (number between 0 and 1). ' .
            'Do not include any extra text or Markdown. Categories should be concise (e.g., billing, technical, account, sales, general).';
    }

    protected function userPrompt(Ticket $ticket): string
    {
        return "Classify the following ticket.\nSubject: {$ticket->subject}\nBody: {$ticket->body}";
    }

    protected function parseJson(string $content): array
    {
        // Try to extract JSON if model added any wrappers
        $trim = trim($content);
        // Remove markdown code fences if present
        if (str_starts_with($trim, '```')) {
            $trim = preg_replace('/^```[a-zA-Z]*\\n|```$/', '', $trim);
        }
        $data = json_decode($trim, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return [];
        }
        return $data;
    }

    protected function dummyResult(Ticket $ticket): array
    {
        $categories = ['billing', 'technical', 'account', 'sales', 'general'];
        $category = $categories[array_rand($categories)];
        return [
            'category' => $category,
            'explanation' => 'Classification disabled; returning a dummy category based on random selection.',
            'confidence' => round(mt_rand(50, 90) / 100, 2),
        ];
    }

    protected function storeResult(Ticket $ticket, array $result): void
    {
        // Refresh to ensure we consider any concurrent user updates
        $ticket->refresh();
        // Only set category if it's not already set (preserve manual changes)
        if (is_null($ticket->category)) {
            $ticket->category = $result['category'] ?? $ticket->category;
        }
        $ticket->classification_explanation = $result['explanation'] ?? null;
        $ticket->classification_confidence = isset($result['confidence']) ? (float) $result['confidence'] : null;
        $ticket->save();
    }
}

class RateLimitException extends \RuntimeException
{
    public function __construct(string $message, public int $retryAfter)
    {
        parent::__construct($message);
    }
}
