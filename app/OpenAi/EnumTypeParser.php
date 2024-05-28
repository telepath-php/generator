<?php

namespace App\OpenAi;

use Illuminate\Support\Facades\Cache;
use OpenAI\Client;

class EnumTypeParser
{
    public function __construct(
        protected Client $ai,
    ) {
        //
    }

    /**
     * @return array{name: string, values: array<array{name: string, comment: string}>}
     */
    public function parse(string $method, string $parameter, string $description): array
    {
        $systemPrompt = <<<'PROMPT'
Generate a JSON object with a `name` field containing the name of a PHP Backed Enum. It should end in Type and be unique considering the method and the parameter passed to you.
It also should include an array of objects in the `values` field with all possible types from the passed description (keep the case as is) in `name` and a comment explaining for what it is in `comment`.
PROMPT;

        $jsonInput = json_encode([
            'method' => $method,
            'parameter' => $parameter,
            'description' => $description,
        ]);

        $result = $this->ai->chat()->create([
            'model' => 'gpt-4o',
            'seed' => 35827,
            'temperature' => 0,
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $jsonInput,
                ],
            ],
        ]);

        Cache::increment('telepath-generator:openai_tokens', $result->usage->totalTokens);

        return json_decode($result->choices[0]->message->content, true);
    }
}
