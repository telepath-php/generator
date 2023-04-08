<?php

namespace App\Parsers;

use App\Telegram\Document;
use Symfony\Component\DomCrawler\Crawler;

abstract class Parser
{

    public function __construct(
        protected Document $document,
        protected Crawler $crawler,
    ) {}

    abstract public function parse(): void;

    protected function sections(): \Generator
    {
        foreach ($this->crawler->filter('h4') as $heading) {
            $nodes = [$heading];

            /** @var \DOMElement $element */
            foreach ((new Crawler($heading))->nextAll() as $element) {
                if (in_array($element->nodeName, ['h4', 'h3', 'h2', 'h1'])) {
                    break;
                }

                $nodes[] = $element;
            }

            yield new Crawler($nodes);
        }
    }

    protected function normalizeText(Crawler $crawler, bool $withLinks = false): string
    {
        $text = '';

        $nodes = $crawler->getNode(0)->childNodes;

        /** @var \DOMNode $node */
        foreach ($nodes as $node) {
            $text .= match ($node->nodeName) {
                'img'   => $node->getAttribute('alt'),
                'a'     => $withLinks ? $this->normalizeLinks($node) : $node->textContent,
                default => $node->textContent,
            };
        }

        return $text;
    }

    protected function normalizeLinks(\DOMElement $node): string
    {
        $doc = new \DOMDocument();
        $importedNode = $doc->importNode($node, true);

        $pageUri = config('tellaptepab.url');                               // https://core.telegram.org/bots/api
        $baseUri = implode('/', array_slice(explode('/', $pageUri), 0, 3)); // https://core.telegram.org

        $href = $importedNode->getAttribute('href');

        $href = match (substr($href, 0, 1)) {
            '#'     => $pageUri . $href,
            '/'     => $baseUri . $href,
            default => $href,
        };

        $importedNode->setAttribute('href', $href);

        $html = $doc->saveHTML($importedNode);

        return $html;
    }

}
