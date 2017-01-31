<?php

namespace Nova\Widget;

use Nova\Support\ServiceProvider;
use Nova\Widget\Factory;


class WidgetServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('widgets', function($app)
        {
            return new Factory($app['app']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('widgets');
    }
}
