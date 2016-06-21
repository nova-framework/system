<?php

namespace Nova\Cache;

use Nova\Cache\CacheManager;
use Nova\Support\ServiceProvider;


class CacheServiceProvider extends ServiceProvider
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
        $this->app->bindShared('cache', function($app)
        {
            return new CacheManager($app);
        });

        $this->registerCommands();
    }

    /**
     * Register the Cache related Console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app->bindShared('command.cache.clear', function($app)
        {
            return new Console\ClearCommand($app['cache'], $app['files']);
        });

        $this->commands('command.cache.clear');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('cache', 'command.cache.clear');
    }

}
