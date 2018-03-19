<?php

namespace Nova\Cache;

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
        $this->app->bindShared('command.cache.clear', function ($app)
        {
            return new Console\ClearCommand($app['cache'], $app['files']);
        });

        $this->app->bindShared('command.cache.forget', function ($app)
        {
            return new Console\ForgetCommand($app['cache']);
        });

        $this->app->bindShared('command.cache.table', function ($app)
        {
            return new Console\CacheTableCommand($app['files']);
        });

        $this->commands('command.cache.clear', 'command.cache.forget', 'command.cache.table');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.cache.clear', 'command.cache.forget', 'command.cache.table');
    }

}
