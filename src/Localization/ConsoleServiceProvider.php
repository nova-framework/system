<?php

namespace Nova\Localization;

use Nova\Localization\Console\LanguagesUpdateCommand;
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
        $this->app->singleton('command.languages.update', function ($app)
        {
            return new LanguagesUpdateCommand($app['language'], $app['files']);
        });

        $this->commands('command.languages.update');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.languages.update');
    }

}
