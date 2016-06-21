<?php

namespace Nova\Routing;

use Nova\Routing\Console\MakeControllerCommand;
use Nova\Routing\Generators\ControllerGenerator;
use Nova\Support\ServiceProvider;


class ControllerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
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
            $path = $app['path'] .DS .'Controllers';

            $generator = new ControllerGenerator($app['files']);

            return new MakeControllerCommand($generator, $path);
        });
    }

    /**
     * Get the Services provided by the Provider.
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
