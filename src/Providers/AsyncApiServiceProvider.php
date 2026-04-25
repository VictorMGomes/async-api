<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Providers;

use Illuminate\Support\ServiceProvider;

class AsyncApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/asyncapi.php',
            'asyncapi'
        );
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'async-api');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->publishes([
            __DIR__.'/../config/asyncapi.php' => config_path('asyncapi.php'),
        ], 'asyncapi-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Victormgomes\AsyncApi\Commands\GenerateAsyncApiDocs::class,
            ]);
        }
    }
}
