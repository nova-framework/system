<?php

namespace Nova\Foundation\Providers;

use Nova\Foundation\Forge;
use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\EnvironmentCommand;


class ForgeServiceProvider extends ServiceProvider
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
        $this->app->bindShared('forge', function($app)
        {
           return new Forge($app);
        });

        $this->app->bindShared('command.environment', function($app)
        {
            return new EnvironmentCommand();
        });

        $this->commands('command.environment');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('forge', 'command.environment');
    }

}
