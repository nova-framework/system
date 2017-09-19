<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Routing\Controller;
use Nova\Routing\RouteFiltererInterface as RouteFilterer;
use Nova\Routing\RouteDependencyResolverTrait;


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
     * The routing filterer implementation.
     *
     * @var \Nova\Routing\RouteFiltererInterface  $filterer
     */
    protected $filterer;


    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Nova\Routing\RouteFiltererInterface  $filterer
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(RouteFilterer $filterer, Container $container = null)
    {
        $this->container = $container;

        $this->filterer = $filterer;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  \Nova\Routing\Controller  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, Request $request, Controller $controller, $method)
    {
        $this->assignAfter($controller, $route, $method);

        //
        $response = $this->before($controller, $route, $request, $method);

        if (is_null($response)) {
            $response = $this->call($controller, $route, $method);
        }

        return $response;
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Nova\Routing\Controller  $controller
     * @param  \Nova\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function call($controller, $route, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );

        return $controller->callAction($method, $parameters);
    }

    /**
     * Call the "before" filters for the controller.
     *
     * @param  \Nova\Routing\Controller  $controller
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return mixed
     */
    protected function before($controller, $route, $request, $method)
    {
        foreach ($controller->getBeforeFilters() as $filter) {
            if (static::methodExcludedByFilter($method, $filter)) {
                continue;
            }

            $response = $this->callFilter($filter, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Apply the applicable after filters to the route.
     *
     * @param  \Nova\Routing\Controller  $controller
     * @param  \Nova\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function assignAfter($controller, $route, $method)
    {
        foreach ($controller->getAfterFilters() as $filter) {
            if (static::methodExcludedByFilter($method, $filter)) {
                continue;
            }

            $filter = $filter['filter'];

            $route->after($filter);
        }
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $filter
     * @return bool
     */
    protected static function methodExcludedByFilter($method, array $filter)
    {
        $options = $filter['options'];

        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }

    /**
     * Call the given controller filter method.
     *
     * @param  array  $filter
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    protected function callFilter($filter, $route, $request)
    {
        extract($filter);

        return $this->filterer->callRouteFilter($filter, $parameters, $route, $request);
    }
}
