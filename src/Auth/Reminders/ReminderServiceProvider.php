<?php

namespace Nova\Auth\Reminders;

use Nova\Auth\Reminders\PasswordBrokerManager;
use Nova\Support\ServiceProvider;


class ReminderServiceProvider extends ServiceProvider
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
        $this->app->bindShared('auth.password', function ($app)
        {
            return new PasswordBrokerManager($app);
        });

        $this->app->bind('auth.reminder.broker', function ($app)
        {
            return $app->make('auth.password')->broker();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('auth.password', 'auth.password.broker');
    }

}
