<?php

namespace Nova\Plugin\Providers;

use Nova\Plugin\Console\PluginListCommand;
use Nova\Plugin\Console\ThemeListCommand;
use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerPluginListCommand();

        $this->registerThemeListCommand();
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

}
