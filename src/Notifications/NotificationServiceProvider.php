<?php

namespace Nova\Notifications;

use Nova\Support\ServiceProvider;

use Nova\Notifications\ChannelManager;


class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Register the Notifications plugin Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('notifications', function ($app)
        {
            return new ChannelManager($app, $app['events']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('notifications');
    }
}
