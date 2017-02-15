<?php

namespace Nova\Routing;

use Nova\Support\ServiceProvider;
use Nova\Routing\Console\MakeControllerCommand;
use Nova\Routing\Generators\ControllerGenerator;


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
        // Console support.
        $this->registerGenerator();

        $this->commands('command.controller.make');
    }

    /**
     * Register the controller generator command.
     *
     * @return void
     */
    protected function registerGenerator()
    {
        $this->app->bindShared('command.controller.make', function($app)
        {
            // The controller generator is responsible for building resourceful controllers
            // quickly and easily for the developers via the Artisan CLI. We'll go ahead
            // and register this command instances in this container for registration.
            $path = $app['path'] .DS .'Http' .DS .'Controllers';

            $generator = new ControllerGenerator($app['files']);

            return new MakeControllerCommand($generator, $path);
        });
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
