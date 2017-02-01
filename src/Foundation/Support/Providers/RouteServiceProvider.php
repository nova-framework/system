<?php

namespace Nova\Foundation\Support\Providers;

use Nova\Routing\Router;
use Nova\Support\ServiceProvider;


class RouteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutes();
    }

    /**
     * Load the application routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        $router = $this->app['router'];

        call_user_func_array(array($this, 'map'), array($router));
    }

    /**
     * Load the standard routes file for the application.
     *
     * @param  string  $path
     * @return mixed
     */
    protected function loadRoutesFrom($path)
    {
        $router = $this->app['router'];

        return require $path;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $router = $this->app['router'];

        return call_user_func_array(array($router, $method), $parameters);
    }
}
