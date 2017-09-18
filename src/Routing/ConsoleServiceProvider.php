<?php

namespace Nova\Routing;

use Nova\Routing\Console\ControllerMakeCommand;
use Nova\Routing\Generators\ControllerGenerator;
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
            $path = $app['path'] .DS .'Controllers';

            $generator = new ControllerGenerator($app['files']);

            return new ControllerMakeCommand($generator, $path);
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
