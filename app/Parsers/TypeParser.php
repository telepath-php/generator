<?php

namespace App\Parsers;

use App\Telegram\Types\Type;
use Symfony\Component\DomCrawler\Crawler;

class TypeParser extends Parser
{

    public function parse(): void
    {
        foreach ($this->sections() as $section) {

            $heading = $section->filter('h4');
            $paragraph = $section->filter('p');
            $fieldTable = $section->filter('table');
            $childClasses = $section->filter('ul');

            $isType = $this->tableHasFields($fieldTable)
                || $this->listsChildClasses($childClasses)
                || $this->descriptionContainsObject($paragraph);

            if (! $isType) {
                continue;
            }

            $name = $heading->text();
            $description = $this->normalizeText($paragraph, true);
            $fields = $this->parseFieldTable($fieldTable);
//            $childClasses = $this->parseChildClasses($childClasses);

            $type = new Type(
                $name,
                $description,
            );
            $type->importFields($fields);

            $this->document->types->add($type);

        }
    }

    protected function parseFieldTable(Crawler $table): array
    {
        $fields = [];

        $table->filter('tbody > tr')->each(function (Crawler $row) use (&$fields) {

            $items = $row->filter('td');

            $fields[] = [
                'field'       => $items->eq(0)->text(),
                'type'        => $items->eq(1)->text(),
                'description' => $this->normalizeText($items->eq(2), true),
            ];

        });

        return $fields;
    }

    protected function parseChildClasses(Crawler $list): array
    {
        return $list->filter('li')
            ->each(fn($item) => $item->text());
    }

    protected function tableHasFields(Crawler $table): bool
    {
        $firstHeading = $table->filter('thead th:nth-child(1)');

        if ($firstHeading->count() === 0) {
            return false;
        }

        return $firstHeading->text() === 'Field';
    }

    protected function listsChildClasses(Crawler $childClasses): bool
    {
        $items = $childClasses->filter('li');

        if ($items->count() === 0) {
            return false;
        }

        $invalidItems = $items->reduce(function (Crawler $node) {
            $link = $node->filter('a');

            return $link->count() !== 1 // There should be exactly one link
                || $node->text() !== $link->text() // There should not be any additional text
                || substr($link->attr('href'), 0, 1) !== '#'; // The link should not lead to external websites
        });

        return $invalidItems->count() === 0;
    }

    protected function descriptionContainsObject(Crawler $paragraph): bool
    {
        return str($paragraph->text())->explode('.')
            ->strOfFirst()->startsWith([
                'This object',
                'Describes',
                'Represents',
            ]);
    }

}
