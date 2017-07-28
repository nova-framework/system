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
        $this->app->bindShared('broadcast', function ($app)
        {
            return new BroadcastManager($app);
        });

        $this->app->singleton('Nova\Broadcasting\Contracts\BroadcasterInterface', function ($app)
        {
            return $app->make('broadcast')->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('broadcast', 'Nova\Broadcasting\Contracts\BroadcasterInterface');
    }
}
