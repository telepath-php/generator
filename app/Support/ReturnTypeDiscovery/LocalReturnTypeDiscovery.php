<?php

namespace App\Support\ReturnTypeDiscovery;

use App\Telegram\Methods\Method;

class LocalReturnTypeDiscovery extends ReturnTypeDiscovery
{
    public function discover(Method $type): ?string
    {
        $description = strip_tags($type->description);

        // Remove a, an
        $description = preg_replace('/\b(a|an)\s/i', '', $description);

        $patterns = [
            '/Returns (?:the (?:[\w]+ )?)?([\w]+) (?:of .*? |object )?on success/ui',
            '/Returns ((?:Array of )?[\w]+) object/ui',
            '/Returns .*? as ((?:Array of )?[\w]+) (?:on success|object)/ui',
            '/On success, (?|if .+? ([\w]+) is returned, otherwise ([\w]+) is returned)/ui',
            '/On success, .*?((?:Array of )?[\w]+) (?:that .*? |objects? )?is returned/ui',
            '/in form of (\w+) object/ui',
        ];

        foreach ($patterns as $pattern) {
            unset($matches);

            if (preg_match($pattern, $description, $matches)) {

                return collect($matches)->slice(1)
                    ->map(fn ($item) => str($item)->ucfirst())
                    ->map(fn ($item) => preg_replace('/\bMessages\b/ui', 'Message', $item))
                    ->join(' or ');

            }

        }

        return null;
    }
}
