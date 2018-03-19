<?php

namespace Nova\Foundation\Providers;

use Nova\Foundation\Forge;
use Nova\Support\Composer;
use Nova\Support\ServiceProvider;


class ConsoleSupportServiceProvider extends ServiceProvider
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

        $this->app->bindShared('forge', function($app)
        {
           return new Forge($app);
        });

        // Register the additional service providers.
        $this->app->register('Nova\Console\ScheduleServiceProvider');
        $this->app->register('Nova\Queue\ConsoleServiceProvider');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('composer', 'forge');
    }

}
