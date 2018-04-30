<?php

namespace Nova\Notifications;

use Nova\Support\ServiceProvider;

use Nova\Notifications\Console\NotificationTableCommand;
use Nova\Notifications\DispatcherInterface;
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
     * Bootstrap the Application Events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the Notifications plugin Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerChannelManager();

        $this->registerCommands();
    }

    protected function registerChannelManager()
    {
        $this->app->bindShared('notifications', function ($app)
        {
            return new ChannelManager($app, $app['events']);
        });
    }

    protected function registerCommands()
    {
        $this->app->singleton('command.notification.table', function ($app)
        {
            return new NotificationTableCommand($app['files']);
        });

        $this->commands('command.notification.table');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'notifications', 'command.notification.table'
        );
    }
}
