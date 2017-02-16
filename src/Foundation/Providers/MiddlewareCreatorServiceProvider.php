<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\MakeMiddlewareCommand;


class MiddlewareCreatorServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.middleware.make', function($app)
        {
            return new MakeMiddlewareCommand($app['files']);
        });

        $this->commands('command.middleware.make');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.middleware.make',
        );
    }

}
