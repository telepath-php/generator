<?php

namespace App\Commands;

use App\Services\Parser;
use App\Services\TypeGenerator;
use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class GenerateTypes extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate:types
                            {--path= : Path to the src folder (Default: src/)}
                            {--namespace= : Namespace Prefix (Default: Tii\\Telepath\\)}
                            {--parent-class= : Parent Class for Type classes (Default: Tii\\Telepath\\Type)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generates Type classes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $content = Cache::remember('telegram-bot-api-page', now()->addDays(1), function () {
            $response = Http::get('https://core.telegram.org/bots/api');
            return $response->body();
        });

        $srcPath = Str::finish($this->option('path') ?? 'src', '/');
        $namespace = Str::finish($this->option('namespace') ?? 'Tii\\Telepath\\', '\\');
        $parentClass = $this->option('parent-class') ?? 'Tii\\Telepath\\Type';

        $parser = resolve(Parser::class, ['namespace' => $namespace, 'parentClass' => $parentClass]);
        $parser->parse($content);

        $generator = resolve(TypeGenerator::class);
        foreach ($parser->types() as $type) {
            $file = $generator->generate($type);
            $path = str_replace([$namespace, '\\'], ['', '/'], $type->namespace) . '/';

            Storage::put($srcPath . $path . $type->name . '.php', $file);
        }
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
