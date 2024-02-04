<?php

namespace App\Parsers;

use App\Telegram\Methods\Method;
use Symfony\Component\DomCrawler\Crawler;

class MethodParser extends Parser
{

    public function parse(): void
    {
        foreach ($this->sections() as $section) {

            $heading = $section->filter('h4');
            $paragraph = $section->filter('p');
            $parameterTable = $section->filter('table');

            $isMethod = $this->tableHasParameters($parameterTable)
                || $this->paragraphContainsMethod($paragraph);

            if (! $isMethod) {
                continue;
            }

            $name = $heading->text();
            $description = $this->normalizeText($paragraph, true);
            $parameters = $this->parseParameterTable($parameterTable);

            $method = new Method(
                $name,
                $description,
            );
            $method->importParameters($parameters);

            $this->document->methods->put($name, $method);

        }
    }

    protected function tableHasParameters(Crawler $table): bool
    {
        $heading = $table->filter('th')->first();

        if ($heading->count() === 0) {
            return false;
        }

        return $heading->text() === 'Parameter';
    }

    protected function paragraphContainsMethod(Crawler $paragraph): bool
    {
        $firstSentence = str($paragraph->text())->explode('.')->first();

        return (bool) preg_match('/(?|this method|A simple method)/ui', $firstSentence);
    }

    protected function parseParameterTable(Crawler $table): array
    {
        return $table->filter('tbody > tr')->each(function (Crawler $row) {

            $items = $row->filter('td');

            return [
                'parameter'   => $items->eq(0)->text(),
                'type'        => $items->eq(1)->text(),
                'required'    => $items->eq(2)->text(),
                'description' => $this->normalizeText($items->eq(3), true),
            ];

        });
    }

}
