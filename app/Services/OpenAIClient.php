<?php

namespace App\Services;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateResponse;

class OpenAIClient
{
    /**
     * @return CreateResponse
     */
    public static function createStructureResponse(
        string $instruction,
        string $input,
        JsonSchema $schema,
    ) {
        return OpenAI::responses()
            ->create([
                'model'        => 'gpt-5',
                'instructions' => $instruction,
                'input'        => $input,
                'text'         => [
                    'format' => [
                        'type'   => 'json_schema',
                        'name'   => Str::snake(class_basename($schema)),
                        'schema' => [...$schema->toArray(), 'additionalProperties' => false] // @phpstan-ignore-line
                    ],
                ],
            ]);
    }
}
