<?php

namespace abdalqader\crudcommand;

use Illuminate\Support\ServiceProvider;
use abdalqader\crudcommand\Commands\CrudCommand;

class CrudServiceProvider extends ServiceProvider
{

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
