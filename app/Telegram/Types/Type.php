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

    public ?string $factoryField = null;
    public ?array $factoryAssociation = null;

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
            $fixedValue = $this->parseFixedValue($dataCells->getNode(2), $description);

            if (str_contains($description, 'attach://') && ! str_contains($type, 'InputFile')) {
                $type .= ' or InputFile';
            }

            $this->fields[] = new Field($field, $type, $description, $fixedValue, $this->namespace);
        }

        $this->fields = $this->fields->sortBy(fn(Field $item) => $item->optional());

        return $this;
    }

    protected function parseFixedValue(\DOMNode $descriptionNode, string $description): ?string
    {
        return $this->tryMustBe($descriptionNode)
            ?? $this->tryAlways($description);
    }

    protected function tryMustBe(\DOMNode $descriptionNode): ?string
    {
        $expectType = false;
        /** @var \DOMNode $node */
        foreach ($descriptionNode->childNodes as $node) {
            if ($node instanceof \DOMText) {
                $text = trim($node->wholeText);
                if (str_ends_with($text, 'must be')) {
                    $expectType = true;
                    continue;
                }
            } elseif ($expectType) {
                return $node->textContent;
            }
        }

        return null;
    }

    protected function tryAlways(string $description)
    {
        if (preg_match('/always “(.+)”/u', $description, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

}
