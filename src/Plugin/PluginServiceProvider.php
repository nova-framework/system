<?php

namespace Nova\Plugin;

use Nova\Plugin\PluginManager;
use Nova\Support\ServiceProvider;


class PluginServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the Provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the Service Provider.
     */
    public function boot()
    {
        $plugins = $this->app['plugins'];

        $plugins->register();
    }

    /**
     * Register the Service Provider.
     */
    public function register()
    {
        $this->app->bindShared('plugins', function ($app)
        {
            return new PluginManager($app, $app['files']);
        });
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return string
     */
    public function provides()
    {
        return array('plugins');
    }

}
