<?php

namespace App\Telegram\Types;

use App\Parsers\Parser;
use App\Parsers\Types\InheritanceType;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class Type
{
    public readonly string $name;

    public readonly string $namespace;

    /** @var Field[] */
    public Collection $fields;

    public function __construct(
        public readonly string $class,
        public readonly ?string $extends = null,
        public readonly ?string $description = null,
        public readonly InheritanceType $inheritanceType = InheritanceType::DEFAULT,
    ) {
        $this->fields = new Collection();

        $namespaceParts = str($class)->explode('\\');
        $this->name = $namespaceParts->last();
        $this->namespace = $namespaceParts->slice(0, -1)->join('\\');
    }

    public function parseTable(\DOMElement $table)
    {
        $crawler = new Crawler($table);

        $rows = $crawler->filter('tbody > tr');

        foreach ($rows as $row) {
            $dataCells = (new Crawler($row))->filter('td');

            $field = $dataCells->getNode(0)->textContent;
            $type = $dataCells->getNode(1)->textContent;
            $description = Parser::parseText($dataCells->getNode(2));

            $this->fields[] = new Field($field, $type, $description, $this->namespace);
        }

        $this->fields = $this->fields->sortBy(fn(Field $item) => $item->optional());

        return $this;
    }
}
