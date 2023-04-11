<?php

namespace App\Parsers;

use App\Telegram\Types\Type;
use Symfony\Component\DomCrawler\Crawler;

class TypeParser extends Parser
{

    public function parse(): void
    {
        foreach ($this->sections() as $section) {

            $name = $this->name($section);

            if (in_array($name, array_keys(config('tellaptepab.type.replace_types')))) {
                continue;
            }

            $description = $this->description($section);
            $fields = $this->fields($section);
            $children = $this->children($section);

            $isType = $fields
                || $children
                || $this->descriptionContainsObject($description);

            if (! $isType) {
                continue;
            }

            $type = new Type(
                $name,
                $description,
            );
            $type->importFields($fields);

            // Set children by name
            foreach ($children as $child) {
                $type->children->put($child, $child);
            }

            // Set parent and replace the childs name with a reference
            $type->parent = $this->document->findParentType($name);
            $type->parent?->children->put($name, $type);

            $this->document->types->put($name, $type);

        }

        $this->document->pullUpCommonFields();
    }

    protected function name(Crawler $section): string
    {
        return $section->filter('h4')->text();
    }

    protected function description(Crawler $section): string
    {
        return $this->normalizeText($section->filter('p'));
    }

    /**
     * @return array<int, array{field: string, type: string, description: string}>
     */
    protected function fields(Crawler $section): array
    {
        $firstHeading = $section->filter('table th')->first();

        if ($firstHeading->count() === 0 || $firstHeading->text() !== 'Field') {
            return [];
        }

        return $section->filter('table tbody tr')->each(function (Crawler $row) {

            $items = $row->filter('td');

            return [
                'field'       => $items->eq(0)->text(),
                'type'        => $items->eq(1)->text(),
                'description' => $this->normalizeText($items->eq(2), true),
            ];

        });
    }

    /**
     * @return string[]
     */
    protected function children(Crawler $section): array
    {
        $items = $section->filter('ul li');

        if ($items->count() === 0) {
            return [];
        }

        $invalidItems = $items->reduce(function (Crawler $node) {
            $link = $node->filter('a');

            return $link->count() !== 1 // There should be exactly one link
                || $node->text() !== $link->text() // There should not be any additional text
                || substr($link->attr('href'), 0, 1) !== '#'; // The link should not lead to external websites
        });

        if ($invalidItems->count() > 0) {
            return [];
        }

        return $items->each(fn(Crawler $item) => $item->text());
    }

    protected function descriptionContainsObject(string $text): bool
    {
        return str($text)->explode('.')
            ->strOfFirst()->startsWith([
                'This object',
                'Describes',
                'Represents',
            ]);
    }

}
