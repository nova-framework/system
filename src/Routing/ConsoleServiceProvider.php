<?php

namespace Nova\Routing;

use Nova\Routing\Console\ControllerMakeCommand;
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
        $this->app->bindShared('command.controller.make', function ($app)
        {
            return new ControllerMakeCommand($app['files']);
        });

        $this->commands('command.controller.make');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.controller.make'
        );
    }

}
