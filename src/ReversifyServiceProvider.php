<?php

namespace Bevanr01\Reversify;

use Illuminate\Support\ServiceProvider;

class ReversifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/config/reversify.php' => config_path('reversify.php'),
        ], 'config');

        // Register command
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ReversifyGenerateCommand::class,
                Commands\ReversifyMigrationsCommand::class,
                Commands\ReversifyControllersCommand::class,
                Commands\ReversifyModelsCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/reversify.php',
            'reversify'
        );
    }
}
