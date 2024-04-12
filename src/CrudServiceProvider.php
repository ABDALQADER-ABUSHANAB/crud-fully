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
     Artisan::command('crud:generate');
}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
