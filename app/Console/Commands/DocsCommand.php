<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class DocsCommand extends Command
{
    protected const API_DOCS_URI = 'https://core.telegram.org/bots/api';

    protected function fetchPage(): string
    {
        return Cache::remember('api-docs', now()->addHours(1), function () {
            $content = Http::get(static::API_DOCS_URI)->body();

            // Telegram sends <!-- page generated in 45.12ms --> after the closing </html> tag
            return preg_replace('#(</html>).+#s', '$1', $content);
        });
    }
}
