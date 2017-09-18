<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Routing\RouteDependencyResolverTrait;
use Nova\Support\Arr;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use Closure;


class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

    /**
     * The routing filterer implementation.
     *
     * @var \Nova\Routing\RouteFiltererInterface  $filterer
     */
    protected $filterer;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;


    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Nova\Routing\RouteFiltererInterface  $filterer
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(RouteFiltererInterface $filterer, Container $container = null)
    {
        $this->filterer = $filterer;

        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  string  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, Request $request, $controller, $method)
    {
        $instance = $this->makeController($controller);

        $this->assignAfter($instance, $route, $request, $method);

        //
        $response = $this->before($instance, $route, $request, $method);

        if (is_null($response)) {
            $response = $this->call($instance, $route, $method);
        }

        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        return $response;
    }

    /**
     * Make a controller instance via the IoC container.
     *
     * @param  string  $controller
     * @return mixed
     */
    protected function makeController($controller)
    {
        return $this->container->make($controller);
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Nova\Routing\Controller  $instance
     * @param  \Nova\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function call($instance, $route, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        return $instance->callAction($method, $parameters);
    }

    /**
     * Call the "before" filters for the controller.
     *
     * @param  \Nova\Routing\Controller  $instance
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return mixed
     */
    protected function before($instance, $route, $request, $method)
    {
        foreach ($instance->getBeforeFilters() as $filter) {
            $options = $filter['options'];

            if ($this->methodExcludedByOptions($method, $options)) {
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
     * @param  \Nova\Routing\Controller  $instance
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return mixed
     */
    protected function assignAfter($instance, $route, $request, $method)
    {
        foreach ($instance->getAfterFilters() as $filter) {
            $options = $filter['options'];

            if (! $this->methodExcludedByOptions($method, $options)) {
                $filter = $filter['filter'];

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
    protected function methodExcludedByOptions($method, array $options)
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
        extract($filter);

        return $this->filterer->callRouteFilter($filter, $parameters, $route, $request);
    }
}
