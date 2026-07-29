<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Victormgomes\AsyncApi\Commands\AsyncApiCommand;
use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;
use Victormgomes\AsyncApi\Services\Docs\SchemaConverter;

class AsyncApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('async-api')
            ->hasConfigFile('async-api')
            ->hasViews('async-api')
            ->hasRoute('web')
            ->hasCommand(AsyncApiCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SchemaConverter::class, function () {
            return new SchemaConverter;
        });

        $this->app->singleton(AsyncApiGenerator::class, function ($app) {
            return new AsyncApiGenerator($app->make(SchemaConverter::class));
        });

        $this->app->singleton(AsyncApi::class, function ($app) {
            return new AsyncApi($app->make(AsyncApiGenerator::class));
        });
    }
}
