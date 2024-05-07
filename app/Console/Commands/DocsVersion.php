<?php

namespace App\Console\Commands;

use Symfony\Component\DomCrawler\Crawler;

class DocsVersion extends DocsCommand
{
    protected $signature = 'docs:version';

    protected $description = 'Identifies the latest Bot API version';

    public function handle(): void
    {
        $content = $this->fetchPage();

        $version = $this->parseVersion($content);

        if ($version === null) {
            return;
        }

        echo $version;
    }

    protected function parseVersion(string $content): ?string
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

        $versionText = $node->textContent;

        if (preg_match('/Bot API (\d+\.\d+)/i', $versionText, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
