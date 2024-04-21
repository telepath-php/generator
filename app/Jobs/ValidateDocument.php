<?php

namespace App\Jobs;

use App\Telegram\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateDocument implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Document $document) {}

    public function handle(): void
    {
        foreach (config('generator.validators') as $validator) {

            $validator = new $validator();

            $validator->validate($this->document);

            // TODO: Do something with generated reports

        }

        dispatch(new GenerateCode($this->document));

    }

}
