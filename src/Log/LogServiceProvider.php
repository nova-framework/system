<?php
/**
 * LogServiceProvider - Implements a Service Provider for Logging.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Log;

use Nova\Support\ServiceProvider;

use Monolog\Logger;


class LogServiceProvider extends ServiceProvider
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
        $logger = new Writer(
            new Logger($this->app['env']), $this->app['events']
        );

        $this->app->instance('log', $logger);

        // If the setup Closure has been bound in the container, we will resolve it
        // and pass in the logger instance. This allows this to defer all of the
        // logger class setup until the last possible second, improving speed.
        if (isset($this->app['log.setup'])) {
            call_user_func($this->app['log.setup'], $logger);
        }

        $this->registerCommands();
    }

    /**
     * Register the Cache related Console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app->bindShared('command.log.clear', function($app)
        {
            return new Console\ClearCommand($app['files']);
        });

        $this->commands('command.log.clear');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('log', 'command.log.clear');
    }

}
