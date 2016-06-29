<?php

namespace Nova\Modules;

use Nova\Modules\Modules;
use Nova\Support\ServiceProvider;


class ModulesServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the Provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $modules = $this->app['modules'];

        $modules->register();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->register('Nova\Modules\Providers\RepositoryServiceProvider');
        $this->app->register('Nova\Modules\Providers\ConsoleServiceProvider');

        //
        $this->app->bindShared('modules', function ($app) {
            $repository = $app->make('Nova\Modules\RepositoryInterface');

            return new Modules($app, $repository);
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
