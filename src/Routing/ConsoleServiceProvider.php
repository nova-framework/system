<?php

namespace Nova\Routing;

use Nova\Routing\Console\ControllerMakeCommand;
use Nova\Routing\Console\MiddlewareMakeCommand;
use Nova\Routing\Generators\ControllerGenerator;
use Nova\Routing\Generators\MiddlewareGenerator;
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
        $this->app->bindShared('command.controller.make', function($app)
        {
            $path = $app['path'] .DS .'Http' .DS .'Controllers';

            $generator = new ControllerGenerator($app['files']);

            return new ControllerMakeCommand($generator, $path);
        });

        $this->app->bindShared('command.middleware.make', function($app)
        {
            $path = $app['path'] .DS .'Http' .DS .'Middleware';

            $generator = new MiddlewareGenerator($app['files']);

            return new MiddlewareMakeCommand($generator, $path);
        });

        $this->commands('command.controller.make', 'command.middleware.make');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.controller.make', 'command.middleware.make'
        );
    }

}
