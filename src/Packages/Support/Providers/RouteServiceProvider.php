<?php

namespace Nova\Packages\Support\Providers;

use Nova\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Nova\Routing\Router;


class RouteServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Define the routes for the module.
     *
     * @param  \Nova\Routing\Router $router
     * @return void
     */
    public function map(Router $router)
    {
        $router->group(array('namespace' => $this->namespace), function ($router)
        {
            $basePath = $this->guessPackageRoutesPath();

            if (is_readable($path = $basePath .DS .'Api.php')) {
                $router->group(array('prefix' => 'api', 'middleware' => 'api'), function ($router) use ($path)
                {
                    require $path;
                });
            }

            if (is_readable($path = $basePath .DS .'Web.php')) {
                $router->group(array('middleware' => 'web'), function ($router) use ($path)
                {
                    require $path;
                });
            }
        });
    }

    /**
     * Guess the package path for the provider.
     *
     * @return string
     */
    public function guessPackageRoutesPath()
    {
        $path = $this->guessPackagePath();

        return $path .DS .'Routes';
    }

    /**
     * Add middleware to the router.
     *
     * @param array $routeMiddleware
     */
    protected function addRouteMiddleware($routeMiddleware)
    {
        if (is_array($routeMiddleware) && (count($routeMiddleware) > 0)) {
            foreach ($routeMiddleware as $key => $middleware) {
                $this->middleware($key, $middleware);
            }
        }
    }

    /**
     * Add middleware groups to the router.
     *
     * @param array $middlewareGroups
     */
    protected function addMiddlewareGroups($middlewareGroups)
    {
        if (is_array($middlewareGroups) && (count($middlewareGroups) > 0)) {
            foreach ($middlewareGroups as $key => $groupMiddleware) {
                foreach ($groupMiddleware as $middleware) {
                    $this->pushMiddlewareToGroup($key, $middleware);
                }
            }
        }
    }
}
