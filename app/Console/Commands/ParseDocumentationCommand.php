<?php

namespace App\Console\Commands;

use App\Jobs\ParseDocumentation;
use Illuminate\Console\Command;

class ParseDocumentationCommand extends Command
{

    protected $signature = 'parse';

    protected $description = 'Parses the Telegram Bot API documentation.';

    public function handle(): void
    {
        dispatch(new ParseDocumentation());
    }

}
