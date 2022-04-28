<?php

namespace App\Services;

use App\Telegram\Method;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class MethodParser
{

    /** @var Method[] */
    public Collection $methods;

    public function __construct(
        protected string $namespace
    ) {
        $this->methods = new Collection();
    }

    public function parse(string $content)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        $methods = $this->filterMethods($crawler);
        foreach ($methods as ['heading' => $heading, 'paragraph' => $paragraph, 'table' => $table]) {

            $name = $heading->textContent;
            $description = Parser::parseText($paragraph);

            $method = new Method($name, $description, $this->namespace);

            if (! is_null($table)) {
                $method->parseTable($table);
            }

            $this->methods[$name] = $method;

        }

        return $this->methods;
    }

    /**
     * @param  Crawler  $crawler
     * @return array{ array{ heading: \DOMElement, paragraph: \DOMElement, table: \DOMElement} }
     */
    protected function filterMethods(Crawler $crawler): array
    {
        $methods = [];

        /** @var \DOMElement $heading */
        foreach ($crawler->filter('h4') as $heading) {
            $paragraph = $this->findNext($heading, 'p', 'h4');
            $table = $this->findNext($heading, 'table', 'h4');

            $isMethod = $this->tableHasParameter($table) || $this->paragraphContainsMethod($paragraph);

            if (! $isMethod) {
                continue;
            }

            $methods[] = ['heading' => $heading, 'paragraph' => $paragraph, 'table' => $table];
        }

        return $methods;
    }

    protected function findNext(\DOMNode $startNode, string $nodeName, string $abort = null): ?\DOMElement
    {
        foreach ((new Crawler($startNode))->nextAll() as $node) {
            if ($abort !== null && $node->nodeName === $abort) {
                return null;
            }

            if ($node instanceof \DOMElement && $node->nodeName === $nodeName) {
                return $node;
            }
        }

        return null;
    }

    protected function tableHasParameter(?\DOMElement $table): bool
    {
        return ! is_null($table) && (new Crawler($table))->filter('th')->first()->text() === 'Parameter';
    }

    protected function paragraphContainsMethod(?\DOMElement $paragraph)
    {
        return str($paragraph->textContent)->explode('.')->strOfFirst()->test('/\bmethod\b/');
    }

}
