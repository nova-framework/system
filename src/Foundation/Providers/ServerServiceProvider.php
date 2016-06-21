<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\ServeCommand;


class ServerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
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
        $this->app->bindShared('command.serve', function()
        {
            return new ServeCommand();
        });

        $this->commands('command.serve');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.serve');
    }

}
