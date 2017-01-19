<?php

namespace Nova\Layout;

use Nova\Layout\Factory as LayoutFactory;
use Nova\Support\ServiceProvider;
use Nova\View\Engines\EngineResolver;


class LayoutServiceProvider extends ServiceProvider
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
        $this->app->bindShared('layout', function($app)
        {
            $factory = $app['view'];

            $finder = $app['view.finder'];

            return new LayoutFactory($factory, $finder, $app['language']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('layout');
    }
}
