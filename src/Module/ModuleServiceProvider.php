<?php

namespace Nova\Module;

use Nova\Module\ModuleManager;
use Nova\Support\ServiceProvider;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the Provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the Service Provider.
     */
    public function boot()
    {
        $modules = $this->app['modules'];

        $modules->register();
    }

    /**
     * Register the Service Provider.
     */
    public function register()
    {
        $this->app->register('Nova\Module\Providers\RepositoryServiceProvider');
        $this->app->register('Nova\Module\Providers\ConsoleServiceProvider');
        $this->app->register('Nova\Module\Providers\GeneratorServiceProvider');

        //
        $this->app->bindShared('modules', function ($app) {
            $repository = $app->make('Nova\Module\Contracts\RepositoryInterface');

            return new ModuleManager($app, $repository);
        });
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return string
     */
    public function provides()
    {
        return array('modules');
    }

}
