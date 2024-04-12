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
        $this->commands([
            CrudCommand::class,
        ]);
    }
}
