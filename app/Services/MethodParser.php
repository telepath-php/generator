<?php

namespace App\Services;

use App\Telegram\Method;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class MethodParser
{

    protected ?string $methodName;
    protected ?string $methodDescription;

    /** @var Method[] */
    public Collection $methods;

    public function __construct(
        protected string $namespace
    )
    {
        $this->methods = new Collection();
    }

    public function parse(string $content)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        $elements = $crawler->filter('h4, table');

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            if ($element->nodeName === 'h4') {
                $this->methodName = $element->textContent;

                foreach ((new Crawler($element))->nextAll() as $pNode) {
                    if ($pNode->nodeName === 'p') {
                        $this->methodDescription = Parser::parseText($pNode);
                        break;
                    }
                }
                continue;
            }

            if ($this->methodName === null) {
                continue;
            }

            if ($element->nodeName === 'table') {
                $this->parseTable(new Crawler($element));
                continue;
            }
        }

        return $this->methods;
    }

    protected function parseTable(Crawler $crawler)
    {
        $firstHeading = $crawler->filter('th')->first()->text();
        if ($firstHeading !== 'Parameter') {
            return;
        }

        // We have a METHOD!
        $this->methods[$this->methodName] = (new Method($this->methodName, $this->methodDescription, $this->namespace))->parseTable($crawler);
    }

}
