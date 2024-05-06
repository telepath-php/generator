<?php

namespace App\Console\Commands;

use App\Jobs\ParseDocumentation;
use Illuminate\Console\Command;

class GenerateCommand extends Command
{

    protected $signature = 'generate
        {path? : The path where the generated code should be saved}
    ';

    protected $description = 'Parses and generates the code';

    public function handle(): void
    {
        $buildPath = $this->argument('path') ?? config('generator.build_path');
        $buildPath = realpath($buildPath);

        $this->info("Generating code in {$buildPath}");
        config()->set('generator.build_path', $buildPath);

        dispatch(new ParseDocumentation());
    }

}
