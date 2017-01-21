<?php

namespace Nova\Queue;

use Nova\Queue\Console\RetryCommand;
use Nova\Queue\Console\ListFailedCommand;
use Nova\Queue\Console\FlushFailedCommand;
use Nova\Queue\Console\FailedTableCommand;
use Nova\Queue\Console\ForgetFailedCommand;
use Nova\Support\ServiceProvider;


class FailConsoleServiceProvider extends ServiceProvider
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
        $this->app->bindShared('command.queue.failed', function()
        {
            return new ListFailedCommand;
        });

        $this->app->bindShared('command.queue.retry', function()
        {
            return new RetryCommand;
        });

        $this->app->bindShared('command.queue.forget', function()
        {
            return new ForgetFailedCommand;
        });

        $this->app->bindShared('command.queue.flush', function()
        {
            return new FlushFailedCommand;
        });

        $this->app->bindShared('command.queue.failed-table', function($app)
        {
            return new FailedTableCommand($app['files']);
        });

        $this->commands(
            'command.queue.failed', 'command.queue.retry', 'command.queue.forget', 'command.queue.flush', 'command.queue.failed-table'
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
            'command.queue.failed', 'command.queue.retry', 'command.queue.forget', 'command.queue.flush', 'command.queue.failed-table',
        );
    }

}
