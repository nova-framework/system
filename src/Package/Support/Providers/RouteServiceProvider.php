<?php

namespace Nova\Package\Support\Providers;

use Nova\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;


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
        $path = $this->guessPackagePath();

        $router->group(array('prefix' => 'api', 'middleware' => 'api', 'namespace' => $this->namespace), function ($router) use ($path)
        {
            require $path .str_replace('/', DS, '/Routes/Api.php');
        });

        $router->group(array('middleware' => 'web', 'namespace' => $this->namespace), function ($router) use ($path)
        {
            require $path .str_replace('/', DS, '/Routes/Api.php');
        });
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
