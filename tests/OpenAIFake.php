<?php

namespace Tests;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAIFake
{
    public static function fake(array $payload = []): void
    {
        OpenAI::swap(new class($payload)
        {
            public function __construct(private readonly array $payload) {}

            public function responses(): object
            {
                return new class($this->payload)
                {
                    public function __construct(private readonly array $payload) {}

                    public function create(array $params): object
                    {
                        return (object) ['outputText' => json_encode($this->payload)];
                    }
                };
            }
        });
    }
}
