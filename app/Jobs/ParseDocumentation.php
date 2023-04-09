<?php

namespace App\Jobs;

use App\Support\PhpTypeMapper;
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
            return Http::get(config('tellaptepab.url'))->body();
        });

        $document = new Document();
        $crawler = new Crawler($page);

        foreach (config('tellaptepab.parser') as $parser) {

            (new $parser($document, $crawler))->parse();

        }

//        $this->debugReturnTypes($document);

        dispatch(new ValidateDocument($document));
    }

    protected function debugReturnTypes(Document $document): void
    {
        ray()->newScreen();
        $missing = 0;
        foreach ($document->methods as $method) {
            $return = $method->return();

            ray()->table([
                'name'        => $method->name,
                'description' => $method->description,
                'return'      => $return,
                'docType'     => $return ? PhpTypeMapper::docType($return) : null,
                'phpType'     => $return ? PhpTypeMapper::phpType($return) : null,
            ])->green()
                ->if(is_null($return))->red();

            if (is_null($return)) {
                $missing++;
            }
        }

        ray("Unrecognized return types: {$missing}");

        ray(Cache::get('openai_tokens', 0))->label('OpenAI Tokens');
    }

}
