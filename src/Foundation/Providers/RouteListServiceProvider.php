<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\RoutesCommand;

class RouteListServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('command.routes', function($app)
        {
            return new RoutesCommand($app['router']);
        });

        $this->commands('command.routes');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.routes');
    }

}
