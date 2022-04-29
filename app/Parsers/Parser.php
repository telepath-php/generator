<?php

namespace App\Parsers;

use Illuminate\Support\Arr;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{

    public static function parseText(\DOMNode $node)
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
