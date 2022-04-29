<?php

namespace App\Commands;

use App\Generators\MethodGenerator;
use App\Parsers\MethodParser;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class GenerateMethods extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate:methods
                            {--path= : Path to the src folder (Default: src/)}
                            {--class= : Classname to generate (Default: Tii\\Telepath\\Telegram)}
                            {--namespace= : Namespace Prefix (Default: Tii\\Telepath\\Telegram\\)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generates methods';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = Http::get('https://core.telegram.org/bots/api');
        $content = $response->body();

        $path = Str::finish($this->option('path') ?? 'src', '/');
        $class = $this->option('class') ?? 'Tii\\Telepath\\Telegram';
        $namespace = Str::finish($this->option('namespace') ?? 'Tii\\Telepath\\Telegram', '\\');

        $parser = new MethodParser($namespace);
        $methods = $parser->parse($content);

        $generator = new MethodGenerator($class, $namespace);
        foreach ($methods as $method) {
            $generator->addMethod($method);
        }

        $filename = str($class)->explode('\\')->last() . '.php';

        File::ensureDirectoryExists($path);
        File::put($path . $filename, $generator->generate());
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
