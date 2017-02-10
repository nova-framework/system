<?php

namespace Nova\Plugin;

use Nova\Plugin\Console\PluginListCommand;
use Nova\Plugin\Console\ThemeListCommand;
use Nova\Plugin\Generators\MakePluginCommand;
use Nova\Plugin\PluginManager;
use Nova\Plugin\Repository;
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
            $repository = new Repository($app['files']);

            return new PluginManager($app, $repository);
        });

        // Register the Forge Commands.
        $this->registerPluginListCommand();

        $this->registerThemeListCommand();

        $this->registerMakePluginCommand();
    }

    /**
     * Register the module:list command.
     */
    protected function registerPluginListCommand()
    {
        $this->app->singleton('command.plugin.list', function ($app)
        {
            return new PluginListCommand($app['plugins']);
        });

        $this->commands('command.plugin.list');
    }

    /**
     * Register the module:list command.
     */
    protected function registerThemeListCommand()
    {
        $this->app->singleton('command.theme.list', function ($app)
        {
            return new ThemeListCommand($app['plugins']);
        });

        $this->commands('command.theme.list');
    }

    /**
     * Register the make:module command.
     */
    private function registerMakePluginCommand()
    {
        $this->app->bindShared('command.make.plugin', function ($app) {
            return new MakePluginCommand($app['files'], $app['plugins']);
        });

        $this->commands('command.make.plugin');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return string
     */
    public function provides()
    {
        return array('plugins', 'command.plugin.list', 'command.theme.list', 'command.make.plugin');
    }

}
