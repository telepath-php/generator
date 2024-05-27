<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use OpenAI\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return \OpenAI::client(config('services.openai.api_key', ''));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Collection::macro('strOfFirst', function () {
            return Str::of($this->first());
        });
    }
}
