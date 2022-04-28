<?php

namespace App\Services;

use App\Telegram\Type;
use Symfony\Component\DomCrawler\Crawler;

class Parser implements \ArrayAccess
{
    protected array $types = [];

    protected ?string $typeName = null;

    protected ?string $typeDescription = null;

    protected array $parents = [];

    public function __construct(
        protected string $namespace,
        protected string $parentClass,
    ) {
    }

    public function parse(string $content)
    {
        $crawler = resolve(Crawler::class);
        $crawler->addHtmlContent($content);

        $elements = $crawler->filter('h4, table, ul');

        foreach ($elements as $element) {
            if ($element->nodeName === 'h4') {
                $this->typeName = $element->textContent;
                foreach ((new Crawler($element))->nextAll() as $pNode) {
                    if ($pNode->nodeName === 'p') {
                        $this->typeDescription = $pNode->textContent;
                        break;
                    }
                }
                continue;
            }

            if ($this->typeName === null) {
                continue;
            }

            $children = new Crawler($element);

            if ($element->nodeName === 'ul') {
                $this->parseList($children);
                continue;
            }

            if ($element->nodeName === 'table') {
                $this->parseTable($children);
                continue;
            }
        }

        $this->extractCommonFields();

        return $this->types;
    }

    /**
     * @return Type[]
     */
    public function types(): array
    {
        return $this->types;
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \Exception('Types are readonly');
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->types[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->types[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \Exception('Types are readonly');
    }

    protected function parseList(Crawler $crawler)
    {
        $listItems = $crawler->filter('li');
        $invalidItems = $listItems->reduce(function (Crawler $node) {
            $link = $node->filter('a');
            return $link->count() !== 1
                || substr($link->attr('href'), 0, 1) !== '#';
        });

        if ($invalidItems->count() > 0) {
            return;
        }

        $childClasses = array_fill_keys(
            collect($listItems)->map->textContent->toArray(),
            $this->typeName
        );
        $this->parents = array_merge($this->parents, $childClasses);

        $class = $this->namespace . 'Telegram\\' . $this->typeName;
        $extends = $this->parentClass;
        $this->types[$this->typeName] = new Type($class, $extends, $this->typeDescription);
        $this->typeName = null;
    }

    protected function parseTable(Crawler $crawler)
    {
        $firstHeading = $crawler->filter('th')->first()->text();
        if ($firstHeading !== 'Field') {
            return;
        }

        // We have a TYPE!
        $class = $this->namespace . 'Telegram\\' . $this->typeName;
        $extends = isset($this->parents[$this->typeName])
            ? $this->namespace . 'Telegram\\' . $this->parents[$this->typeName]
            : $this->parentClass;

        $this->types[$this->typeName] = (new Type($class, $extends, $this->typeDescription))->parseTable($crawler);
        $this->typeName = null;
    }

    protected function extractCommonFields()
    {
        $parents = collect($this->parents)
            ->mapToGroups(fn($item, $key) => [$item => $key]);

        foreach ($parents as $parent => $children) {

            // Collect common field names
            $commonFieldNames = $this->types[$children->first()]->fields->pluck('name');
            foreach ($children as $child) {
                $commonFieldNames = $commonFieldNames->intersect($this->types[$child]->fields->pluck('name'));
            }

            // Remove common fields from children
            foreach ($children as $child) {
                $this->types[$child]->fields = $this->types[$child]->fields->whereNotIn('name', $commonFieldNames);
            }

            // Add common fields to parent
            $this->types[$parent]->fields = $this->types[$children->first()]->fields->whereIn('name', $commonFieldNames);

        }
    }
}
