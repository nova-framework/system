<?php

namespace Nova\Plugin;

use Nova\Plugin\Console\PluginListCommand;
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

        $this->registerListCommand();
    }

    /**
     * Register the module:list command.
     */
    protected function registerListCommand()
    {
        $this->app->singleton('command.plugin.list', function ($app)
        {
            return new PluginListCommand($app['plugins']);
        });

        $this->commands('command.plugin.list');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return string
     */
    public function provides()
    {
        return array('plugins', 'command.plugin.list');
    }

}
