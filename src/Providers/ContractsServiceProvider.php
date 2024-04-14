<?php

namespace abdalqader\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use abdalqader\Modules\Contracts\RepositoryInterface;
use abdalqader\Modules\Laravel\LaravelFileRepository;

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
