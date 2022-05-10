<?php

namespace App\Parsers\Types;

use App\Parsers\Parser;
use App\Telegram\Types\Field;
use App\Telegram\Types\Type;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class TypeParser extends Parser
{
    public Collection $types;

    public function __construct(
        protected string $namespace,
        protected string $parentClass,
    ) {
        $this->types = new Collection();
    }

    public function parse(string $content)
    {
        $crawler = resolve(Crawler::class);
        $crawler->addHtmlContent($content);

        $types = $this->filterTypes($crawler);

        $inheritance = $this->parseInheritance($types);

        foreach ($types as ['heading' => $heading, 'paragraph' => $paragraph, 'list' => $list, 'table' => $table]) {

            $typeName = $heading->textContent;
            $description = static::parseText($paragraph);

            // Blacklist
            if (in_array($typeName, config('telegram.ignore_types'))) {
                continue;
            }

            $class = $this->namespace . 'Telegram\\' . $typeName;

            $extends = $this->parentClass;
            $inheritanceType = InheritanceType::DEFAULT;

            if (isset($inheritance[$typeName])) {
                $extends = $this->namespace . 'Telegram\\' . $inheritance[$typeName];
                $inheritanceType = InheritanceType::CHILD;
            } elseif (in_array($typeName, $inheritance)) {
                $inheritanceType = InheritanceType::PARENT;
            }

            $type = new Type($class, $extends, $description, $inheritanceType);

            if (! is_null($table)) {
                $type->parseTable($table);
            }

            $this->types[$typeName] = $type;
        }

        $this->extractCommonFields($inheritance);

        return $this->types;
    }

    protected function filterTypes(Crawler $crawler)
    {
        $types = [];

        /** @var \DOMElement $heading */
        foreach ($crawler->filter('h4') as $heading) {
            $paragraph = $this->findNext($heading, 'p', ['h3', 'h4']);
            $list = $this->findNext($heading, 'ul', ['h3', 'h4']);
            $table = $this->findNext($heading, 'table', ['h3', 'h4']);

            $isType = $this->tableHasField($table) || $this->listsChildClasses($list) || $this->paragraphContainsObject($paragraph);

            if (! $isType) {
                continue;
            }

            $types[] = [
                'heading'   => $heading,
                'paragraph' => $paragraph,
                'list'      => $list,
                'table'     => $table,
            ];
        }

        return $types;
    }

    protected function extractCommonFields(array $inheritance)
    {
        $parents = collect($inheritance)
            ->mapToGroups(fn($item, $key) => [$item => $key]);

        foreach ($parents as $parent => $children) {

            // Collect common field names
            $commonFieldNames = $this->types[$children->first()]->fields->pluck('name');
            foreach ($children as $child) {
                $commonFieldNames = $commonFieldNames->intersect($this->types[$child]->fields->pluck('name'));
            }

            // Add common fields to parent
            foreach ($this->types[$children->first()]->fields as $childField) {
                /** @var Field $childField */
                if (! $commonFieldNames->contains($childField->name)) {
                    continue;
                }

                $this->types[$parent]->fields[] = $field = clone $childField;
                $field->fixedValue = null;
                $field->description = preg_replace('/, (must be|always) .+$/u', '', $field->description);
            }

            // Remove common fields from children
            foreach ($children as $child) {
                $this->types[$child]->fields->each(function (Field $field) use ($commonFieldNames) {
                    if ($commonFieldNames->contains($field->name) && $field->fixedValue === null) {
                        $field->property = false;
                    }
                });
            }

        }
    }

    protected function tableHasField(?\DOMElement $table): bool
    {
        return ! is_null($table) && (new Crawler($table))->filter('th')->first()->text() === 'Field';
    }

    protected function listsChildClasses(?\DOMElement $list)
    {
        if (is_null($list)) {
            return false;
        }

        $items = (new Crawler($list))->filter('li');
        $invalidItems = $items->reduce(function (Crawler $node) {
            $link = $node->filter('a');
            return $link->count() !== 1
                || substr($link->attr('href'), 0, 1) !== '#';
        });

        return $invalidItems->count() === 0;
    }

    protected function parseInheritance(array $types)
    {
        $inheritance = [];

        foreach ($types as ['heading' => $heading, 'paragraph' => $paragraph, 'list' => $list, 'table' => $table]) {
            if (is_null($list)) {
                continue;
            }

            $parent = $heading->textContent;
            $children = array_fill_keys(
                collect((new Crawler($list))->filter('li'))
                    ->map->textContent->toArray(),
                $parent
            );

            $inheritance = array_merge($inheritance, $children);
        }

        return $inheritance;
    }

    protected function paragraphContainsObject(?\DOMElement $paragraph)
    {
        return str($paragraph->textContent)->explode('.')->strOfFirst()->test('/\bobject\b/');
    }
}
