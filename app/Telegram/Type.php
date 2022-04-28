<?php

namespace App\Telegram;

use App\Services\Parser;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class Type implements \ArrayAccess
{
    public readonly string $name;

    public readonly string $namespace;

    /** @var Field[] */
    public Collection $fields;

    public function __construct(
        public readonly string $class,
        public readonly ?string $extends = null,
        public readonly ?string $description = null,
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

        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fields[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->fields[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \Exception('Fields are readonly');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \Exception('Fields are readonly');
    }
}
