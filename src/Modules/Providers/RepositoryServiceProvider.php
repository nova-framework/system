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

        $driver = ucfirst($config['modules.driver']);

        if ($driver == 'Custom') {
            $className = $config['modules.custom_driver'];
        } else {
            $className = 'Nova\Modules\Repositories\\' .$driver .'Repository';
        }

        $this->app->bind('Nova\Modules\RepositoryInterface', $className);
    }
}
