<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\KeyGenerateCommand;

class KeyGeneratorServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.key.generate', function($app)
        {
            return new KeyGenerateCommand($app['files']);
        });

        $this->commands('command.key.generate');
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.key.generate');
    }

}
