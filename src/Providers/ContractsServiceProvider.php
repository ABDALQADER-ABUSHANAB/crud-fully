<?php

namespace abdalqader\crudcommand\Providers;

use Illuminate\Support\ServiceProvider;
use abdalqader\crudcommand\Contracts\RepositoryInterface;
use abdalqader\crudcommand\Laravel\LaravelFileRepository;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    public function register()
    {
        $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
    }
}
