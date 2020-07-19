<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Routing\RouteDependencyResolverTrait;
use Nova\Support\Arr;

use Closure;


class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;


    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, Request $request, $controller, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );

        if (! method_exists($controller, 'callAction')) {
            return call_user_func_array(array($controller, $method), $parameters);
        }

        return $controller->callAction($method, $parameters, $request);
    }

    /**
     * Runs the controller method and returns the response.
     *
     * @param  mixed  $controller
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    protected function run($controller, $method, $parameters)
    {
        return call_user_func_array(array($controller, $method), $parameters);
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  mixed  $controller
     * @param  string  $method
     * @return array
     */
    public static function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return array();
        }

        $middleware = $controller->getMiddleware();

        return array_keys(array_filter($middleware, function ($options) use ($method)
        {
            return ! static::methodExcludedByOptions($method, $options);
        }));
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     * @return bool
     */
    protected static function methodExcludedByOptions($method, array $options)
    {
        return (! empty($option = Arr::get($options, 'only')) && ! in_array($method, (array) $option)) ||
               (! empty($option = Arr::get($options, 'except')) && in_array($method, (array) $option));
    }
}
