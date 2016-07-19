<?php

namespace Nova\Remote;

use Nova\Remote\RemoteManager;
use Nova\Support\ServiceProvider;


class RemoteServiceProvider extends ServiceProvider
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
        $this->app->bindShared('remote', function($app)
        {
            return new RemoteManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('remote');
    }

}
