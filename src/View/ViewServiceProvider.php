<?php

namespace Nova\View;

use Nova\View\Factory;
use Nova\View\LayoutFactory;
use Nova\Support\ServiceProvider;


class ViewServiceProvider extends ServiceProvider
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
        $this->app->bindShared('view', function($app)
        {
            return new Factory($app);
        });

        $this->app->bindShared('template', function($app)
        {
            return new LayoutFactory($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('view', 'template');
    }
}
