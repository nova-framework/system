<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Routing\Controller;
use Nova\Routing\RouteFiltererInterface as RouteFilterer;
use Nova\Routing\Route;
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

        if (! is_null($response = $this->before($controller, $route, $request, $method))) {
            return $response;
        }

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
        foreach ($controller->getBeforeFilters() as $filter => $options) {
            if (static::methodExcludedByOptions($method, $options)) {
                continue;
            }

            $response = $this->callFilter($filter, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Apply the applicable "after" filters to the route.
     *
     * @param  \Nova\Routing\Controller  $controller
     * @param  \Nova\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function assignAfter($controller, $route, $method)
    {
        foreach ($controller->getAfterFilters() as $filter => $options) {
            if (! static::methodExcludedByOptions($method, $options)) {
                $route->after($filter);
            }
        }
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
        list($filter, $parameters) = Route::parseFilter($filter);

        return $this->filterer->callRouteFilter($filter, $parameters, $route, $request);
    }
}
