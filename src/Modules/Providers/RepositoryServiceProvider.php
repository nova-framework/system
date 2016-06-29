<?php

namespace Nova\Modules\Providers;

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
        $config = $this->app['config'];

        $driver = $this->app['config']['modules.driver'];

        if ($driver == 'custom') {
            $className = $config['modules.custom_driver'];
        } else {
            $className = 'Nova\Modules\Repositories\\' .ucfirst($driver) .'Repository';
        }

        $this->app->bind('Nova\Modules\RepositoryInterface', $className);
    }
}
