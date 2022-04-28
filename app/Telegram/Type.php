<?php

namespace App\Telegram;

use Symfony\Component\DomCrawler\Crawler;

class Type implements \ArrayAccess
{
    public readonly string $name;
    public readonly string $namespace;

    protected array $fields = [];

    public function __construct(
        public readonly string $class,
        public readonly ?string $extends = null
    ) {
        $namespaceParts = str($class)->explode('\\');

        $this->name = $namespaceParts->last();
        $this->namespace = $namespaceParts->slice(0, -1)->join('\\');
    }

    public function parseTable(Crawler $table)
    {
        $rows = $table->filter('tbody > tr');

        foreach ($rows as $row) {
            $dataCells = (new Crawler($row))->filter('td');

            $field = $dataCells->getNode(0)->textContent;
            $type = $dataCells->getNode(1)->textContent;
            $description = $dataCells->getNode(2)->textContent;

            $this->fields[] = new Field($field, $type, $description, $this->namespace);
        }

        return $this;
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return $this->fields;
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
