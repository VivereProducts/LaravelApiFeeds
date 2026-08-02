<?php

namespace VivereStage\LaravelApiFeeds\Providers;

use Illuminate\Support\ServiceProvider;
use VivereStage\LaravelApiFeeds\Console\Commands\MakeModel;

class VivereApiFeedsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/vivere-api-feeds.php',
            'vivere-api-feeds'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/vivere-api-feeds.php' => config_path('vivere-api-feeds.php'),
            ], 'vivere-api-feeds-config');

            $this->commands([
                MakeModel::class,
            ]);
        }
    }
}
