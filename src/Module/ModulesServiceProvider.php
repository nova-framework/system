<?php

namespace Nova\Module;

use Nova\Module\Modules;
use Nova\Support\ServiceProvider;


class ModulesServiceProvider extends ServiceProvider
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
        $this->app->register('Nova\Modules\Providers\RepositoryServiceProvider');
        $this->app->register('Nova\Modules\Providers\ConsoleServiceProvider');
        $this->app->register('Nova\Modules\Providers\GeneratorServiceProvider');

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
