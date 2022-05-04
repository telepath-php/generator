<?php

namespace App\Parsers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{

    public static function phpDocType(string $type, string $namespace = ''): string
    {
        if (str_starts_with($type, 'Array of')) {
            $subType = substr($type, 9);
            $arrayType = static::phpDocType($subType, $namespace);
            return str_contains($arrayType, '|')
                ? str_replace('|', '[]|', $arrayType) . '[]'
                : $arrayType . '[]';
        }

        $parts = str($type)->split('/(?: or |, | and )/', 2);
        if (count($parts) > 1) {
            return static::phpDocType($parts[0], $namespace) . '|' . static::phpDocType($parts[1], $namespace);
        }

        $replace = config('telegram.replace');

        $fullyQualifiedClassname = isset($replace[$type])
            ? $replace[$type]
            : Str::finish($namespace, '\\') . $type;

        $type = match ($type) {
            'String'                   => 'string',
            'Integer'                  => 'int',
            'Float', 'Float number'    => 'float',
            'Boolean', 'True', 'False' => 'bool',
            default                    => $fullyQualifiedClassname
        };

        return $type;
    }

    public static function phpType(string $type, string $namespace = ''): string
    {
        $type = static::phpDocType($type, $namespace);

        if (str_ends_with($type, '[]')) {
            return 'array';
        }

        return $type;
    }

    public static function parseText(\DOMNode $node): string
    {
        $text = '';

        /** @var \DOMNode $childNode */
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMText) {
                $text .= $childNode->wholeText;
                continue;
            }

            if ($childNode instanceof \DOMElement && $childNode->nodeName === 'img') {
                $text .= $childNode->getAttribute('alt') ?? '';
                continue;
            }

            $text .= $childNode->textContent;
        }

        return $text;
    }

    protected function findNext(\DOMNode $startNode, string $nodeName, string|array $abort = null): ?\DOMElement
    {
        $abort = Arr::wrap($abort);

        foreach ((new Crawler($startNode))->nextAll() as $node) {
            if ($abort !== null && in_array($node->nodeName, $abort)) {
                return null;
            }

            if ($node instanceof \DOMElement && $node->nodeName === $nodeName) {
                return $node;
            }
        }

        return null;
    }

}
