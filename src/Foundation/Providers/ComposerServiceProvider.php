<?php

namespace Nova\Foundation\Providers;

use Nova\Foundation\Composer;
use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\AutoloadCommand;


class ComposerServiceProvider extends ServiceProvider
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
        $this->app->bindShared('composer', function($app)
        {
            return new Composer($app['files'], $app['path.base']);
        });

        $this->app->bindShared('command.dump-autoload', function($app)
        {
            return new AutoloadCommand($app['composer']);
        });

        $this->commands('command.dump-autoload');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('composer', 'command.dump-autoload');
    }

}
