<?php

namespace Nova\Module\Providers;

use Nova\Support\ServiceProvider;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $driver = $this->app['config']['modules.driver'];

        if ($driver == 'custom') {
            $className = $this->app['config']['modules.custom_driver'];
        } else {
            $className = 'Nova\Module\Repositories\\' .ucfirst($driver) .'Repository';
        }

        $this->app->bind('Nova\Module\Contracts\RepositoryInterface', $className);
    }
}
