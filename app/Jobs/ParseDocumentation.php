<?php

namespace App\Jobs;

use App\Telegram\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ParseDocumentation implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $page = Cache::remember('documentation', now()->addHours(24), function () {
            return Http::get(config('parser.url'))->body();
        });

        $document = new Document();
        $crawler = new Crawler($page);

        foreach (config('parser.parser') as $parser) {

            (new $parser($document, $crawler))->parse();

        }

        ray($document);
    }

}
