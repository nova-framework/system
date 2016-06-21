<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\CommandMakeCommand;


class CommandCreatorServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.command.make', function($app)
        {
            return new CommandMakeCommand($app['files']);
        });

        $this->commands('command.command.make');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.command.make',
        );
    }

}
