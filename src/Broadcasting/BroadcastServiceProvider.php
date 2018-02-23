<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\BroadcastManager;

use Nova\Support\ServiceProvider;


class BroadcastServiceProvider extends ServiceProvider
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
        $this->app->singleton('Nova\Broadcasting\BroadcastManager', function ($app)
        {
            return new BroadcastManager($app);
        });

        $this->app->singleton('Nova\Broadcasting\BroadcasterInterface', function ($app)
        {
            return $app->make('Nova\Broadcasting\BroadcastManager')->connection();
        });

        $this->app->alias(
            'Nova\Broadcasting\BroadcastManager', 'Nova\Contracts\Broadcasting\FactoryInterface'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Nova\Broadcasting\BroadcastManager',
            'Nova\Contracts\Broadcasting\FactoryInterface',
            'Nova\Broadcasting\BroadcasterInterface',
        );
    }
}
