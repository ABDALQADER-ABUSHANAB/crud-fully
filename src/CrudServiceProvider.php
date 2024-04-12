<?php

namespace abdalqader\CrudCommand;

use Illuminate\Support\ServiceProvider;
use abdalqader\CrudCommand\Commands\CrudCommand;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
public function register()
{
     
}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
        $this->commands([
            CrudCommand::class,
        ]);
    }
    }
}
