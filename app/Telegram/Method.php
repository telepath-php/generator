<?php

namespace App\Telegram;

use App\Parsers\Parser;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class Method
{

    /** @var Parameter[] */
    public Collection $parameter;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
        protected ?string $namespace = null
    )
    {
        $this->parameter = new Collection();
    }

    public function parseTable(\DOMElement $table)
    {
        $rows = (new Crawler($table))->filter('tbody > tr');

        foreach ($rows as $row) {
            $dataCells = (new Crawler($row))->filter('td');

            $name = $dataCells->getNode(0)->textContent;
            $type = $dataCells->getNode(1)->textContent;
            $required = $dataCells->getNode(2)->textContent;
            $description = Parser::parseText($dataCells->getNode(3));

            $this->parameter[] = new Parameter($name, $type, $required, $description, $this->namespace);
        }

        return $this;
    }

}
