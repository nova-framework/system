<?php

namespace Nova\View;

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
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('command.view.clear', function($app)
        {
            $cachePath = $app['config']['view.compiled'];

            return new Console\ClearCommand($app['files'], $cachePath);
        });

        $this->commands('command.view.clear');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.view.clear');
    }
}
