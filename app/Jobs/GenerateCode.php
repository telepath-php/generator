<?php

namespace App\Jobs;

use App\Generators\Generator;
use App\Telegram\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCode implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Document $document,
    ) {}

    public function handle(): void
    {
        /** @var Generator $generator */
        foreach (config('tellaptepab.generators') as $generator) {

            (new $generator)->generate($this->document);

        }
    }

}
