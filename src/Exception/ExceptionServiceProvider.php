<?php

namespace Nova\Exception;

use Nova\Exception\Handler as ExceptionHandler;
use Nova\Support\ServiceProvider;


class ExceptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['exception'] = $this->app->share(function ($app)
        {
            return new ExceptionHandler($app);
        });
    }
}
