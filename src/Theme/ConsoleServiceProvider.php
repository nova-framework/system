<?php

namespace Nova\Theme;

use Nova\Theme\Console\ThemeMakeCommand;
use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('command.make.theme', function ($app)
        {
            return new ThemeMakeCommand($app['files'], $app['config'], $app['packages']);
        });

        $this->commands('command.make.theme');
    }
}
