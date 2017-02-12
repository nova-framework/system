<?php

namespace Nova\Auth\Reminders;

use Nova\Auth\Console\RemindersTableCommand;
use Nova\Auth\Console\ClearRemindersCommand;
use Nova\Auth\Console\RemindersControllerCommand;
use Nova\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.auth.reminders', function($app)
        {
            return new RemindersTableCommand($app['files']);
        });

        $this->app->bindShared('command.auth.reminders.clear', function()
        {
            return new ClearRemindersCommand;
        });

        $this->app->bindShared('command.auth.reminders.controller', function($app)
        {
            return new RemindersControllerCommand($app['files']);
        });

        $this->commands(
            'command.auth.reminders', 'command.auth.reminders.clear', 'command.auth.reminders.controller'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.auth.reminders');
    }

}
