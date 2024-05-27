<?php

namespace App\Support\ReturnTypeDiscovery;

use App\Telegram\Methods\Method;
use Illuminate\Support\Facades\Cache;
use OpenAI\Client;

class OpenAiReturnTypeDiscovery extends ReturnTypeDiscovery
{
    public function discover(Method $type): ?string
    {
        $ai = resolve(Client::class);

        $prompt = view('prompts.return-type', [
            'name' => $type->name,
            'description' => strip_tags($type->description),
        ])->render();

        $result = $ai->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => trim($prompt),
            'temperature' => 0,
            'stop' => "\n",
            'presence_penalty' => 2,
        ]);

        Cache::increment('openai_tokens', $result->usage->totalTokens);

        return trim(preg_replace('/\bobjects?$/i', '', $result->choices[0]->text));
    }
}
