<?php

namespace App\Console\Commands;

use Symfony\Component\DomCrawler\Crawler;

class DocsChangelog extends DocsCommand
{
    protected $signature = 'docs:changelog';

    protected $description = 'Fetches the latest changelog from the Telegram API docs';

    public function handle(): void
    {
        $content = $this->fetchPage();

        $changelog = $this->parseChangelog($content);

        echo $changelog;
    }

    protected function parseChangelog(string $content): string
    {
        $crawler = new Crawler($content);

        $recentChanges = $crawler->filter('[name="recent-changes"]');
        $node = $recentChanges->getNode(0)->parentNode;
        while ($node->nodeName !== 'h4') {
            $node = $node->nextSibling;
        }

        while ($node->nodeName !== 'p') {
            $node = $node->nextSibling;
        }

        $changelog = $this->formatChangelog($node);

        return $changelog;
    }

    protected function formatChangelog(\DOMNode $node): string
    {
        $text = '';

        do {

            if ($node->nodeName === 'h4') {
                break;
            } elseif ($node->nodeName === 'p') {
                $text .= $this->formatText($node);
            } elseif ($node->nodeName === 'ul') {
                $text .= $this->formatList($node->childNodes);
            } elseif ($node->nodeName !== '#text') {
                $text .= $node->textContent.'[~]'.PHP_EOL;
            }

        } while ($node = $node->nextSibling);

        return $text;
    }

    protected function formatLink(\DOMElement $node): string
    {
        $href = $node->getAttribute('href');

        if (str_starts_with($href, '#')) {
            $href = 'https://core.telegram.org/bots/api'.$href;
        }

        return '['.$node->textContent.']('.$href.')';
    }

    protected function formatText(\DOMNode $parentNode): string
    {
        $text = '';

        /** @var \DOMNode $node */
        foreach ($parentNode->childNodes as $node) {

            $text .= match ($node->nodeName) {
                'strong' => ' **'.trim($node->textContent).'** ',
                'a' => ' '.$this->formatLink($node).' ',
                'em' => ' _'.trim($node->textContent).'_ ',
                '#text' => trim($node->textContent),
                'code' => ' `'.$node->textContent.'` ',
                default => ' '.$node->textContent.' ',
            };
        }
        $text .= PHP_EOL;

        return $text;
    }

    protected function formatList(\DOMNodeList $nodelist): string
    {
        $text = '';

        foreach ($nodelist as $node) {
            if ($node->nodeName !== 'li') {
                continue;
            }

            $text .= '- '.$this->formatText($node);
        }

        return $text;
    }
}
