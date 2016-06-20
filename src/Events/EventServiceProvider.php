<?php
/**
 * EventServiceProvider - Implements a Service Provider for Events Dispatcher.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Events;

use Nova\Support\ServiceProvider;
use Nova\Events\Dispatcher;


class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['events'] = $this->app->share(function($app) {
            return new Dispatcher($app);
        });
    }
}
