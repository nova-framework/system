<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\UpCommand;
use Nova\Foundation\Console\DownCommand;


class MaintenanceServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.up', function()
        {
            return new UpCommand;
        });

        $this->app->bindShared('command.down', function()
        {
            return new DownCommand;
        });

        $this->commands('command.up', 'command.down');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.up', 'command.down');
    }

}
