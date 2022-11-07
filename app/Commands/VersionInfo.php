<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\DomCrawler\Crawler;

class VersionInfo extends Command
{

    protected $signature = 'info:version';

    protected $description = 'Fetches latest Bot API version from the docs page.';

    public function handle()
    {
        $response = Http::get('https://core.telegram.org/bots/api');
        $content = $response->body();

        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        $recentChanges = $crawler->filter('[name="recent-changes"]');
        $node = $recentChanges->getNode(0)->parentNode;
        while ($node->nodeName !== 'h4') {
            $node = $node->nextSibling;
        }

        while ($node->nodeName !== 'p') {
            $node = $node->nextSibling;
        }

        $versionString = $node->textContent;

        $success = preg_match('/Bot API (\d+\.\d+)/i', $versionString, $matches) === 1;

        if (! $success) {
            $this->error('Could not identify latest Bot API version.');
            return Command::FAILURE;
        }

        $this->info($matches[1]);
        return Command::SUCCESS;
    }

}
