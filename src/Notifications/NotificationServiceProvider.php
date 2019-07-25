<?php

namespace Nova\Notifications;

use Nova\Bus\DispatcherInterface as BusDispatcher;
use Nova\Notifications\ChannelManager;
use Nova\Notifications\NotificationSender;
use Nova\Support\ServiceProvider;


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
        $this->app->singleton('notifications.sender', function ($app)
        {
            $bus = $app->make(BusDispatcher::class);

            return new NotificationSender($app['events'], $bus);
        });

        $this->app->singleton('notifications', function ($app)
        {
            return new ChannelManager($app, $app['notifications.sender']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('notifications', 'notifications.sender');
    }
}
