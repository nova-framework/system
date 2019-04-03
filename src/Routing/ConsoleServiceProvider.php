<?php

namespace Nova\Routing;

use Nova\Routing\Console\ControllerMakeCommand;
use Nova\Routing\Console\MiddlewareMakeCommand;
use Nova\Routing\Console\RouteListCommand;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
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
        $this->app->singleton('command.controller.make', function($app)
        {
            return new ControllerMakeCommand($app['files']);
        });

        $this->app->singleton('command.middleware.make', function($app)
        {
            return new MiddlewareMakeCommand($app['files']);
        });

        $this->app->singleton('command.route.list', function ($app)
        {
            return new RouteListCommand($app['router']);
        });

        $this->commands('command.controller.make', 'command.middleware.make', 'command.route.list');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.controller.make', 'command.middleware.make', 'command.route.list'
        );
    }

}
