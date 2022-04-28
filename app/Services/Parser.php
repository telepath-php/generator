<?php

namespace App\Services;

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

}
