<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use OpenAI\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

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

        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
