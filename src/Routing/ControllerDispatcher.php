<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Pipeline\Pipeline;
use Nova\Routing\Router;
use Nova\Routing\RouteDependencyResolverTrait;

use Closure;


class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

    /**
     * The routing filterer implementation.
     *
     * @var \Nova\Routing\Contracts\RouteFiltererInterface  $router
     */
    protected $router;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Nova\Routing\Router $router
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Router $router, Container $container = null)
    {
        $this->router = $router;
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

        return $this->callWithinStack($instance, $route, $request, $method);
    }

    /**
     * Make a controller instance via the IoC container.
     *
     * @param  string  $controller
     * @return mixed
     */
    protected function makeController($controller)
    {
        Controller::setRouter($this->router);

        return $this->container->make($controller);
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Nova\Routing\Controller  $instance
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return mixed
     */
    protected function callWithinStack($instance, $route, $request, $method)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                ($this->container->make('middleware.disable') === true);

        $middleware = $shouldSkipMiddleware ? array() : $this->getMiddleware($instance, $method);

        // Here we will make a stack onion instance to execute this request in, which gives
        // us the ability to define middlewares on controllers. We will return the given
        // response back out so that "after" filters can be run after the middlewares.
        $pipeline = new Pipeline($this->container);

        return $pipeline->send($request)
            ->through($middleware)
            ->then(function ($request) use ($instance, $route, $method)
            {
                return $this->router->prepareResponse(
                    $request, $this->call($instance, $route, $method)
                );
            });
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Nova\Routing\Controller  $instance
     * @param  string  $method
     * @return array
     */
    protected function getMiddleware($instance, $method)
    {
        $results = array();

        foreach ($instance->getMiddleware() as $name => $options) {
            if (! $this->methodExcludedByOptions($method, $options)) {
                $results[] = $this->router->resolveMiddleware($name);
            }
        }

        return $results;
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     * @return bool
     */
    public function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
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
            if ($this->filterApplies($filter, $request, $method)) {
                // Here we will just check if the filter applies. If it does we will call the filter
                // and return the responses if it isn't null. If it is null, we will keep hitting
                // them until we get a response or are finished iterating through this filters.
                $response = $this->callFilter($filter, $route, $request);

                if (! is_null($response)) return $response;
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
            // If the filter applies, we will add it to the route, since it has already been
            // registered on the filterer by the controller, and will just let the normal
            // router take care of calling these filters so we do not duplicate logics.
            if ($this->filterApplies($filter, $request, $method)) {
                $route->after($this->getAssignableAfter($filter));
            }
        }
    }

    /**
     * Get the assignable after filter for the route.
     *
     * @param  \Closure|string  $filter
     * @return string
     */
    protected function getAssignableAfter($filter)
    {
        return $filter['original'] instanceof Closure ? $filter['filter'] : $filter['original'];
    }

    /**
     * Determine if the given filter applies to the request.
     *
     * @param  array  $filter
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return bool
     */
    protected function filterApplies($filter, $request, $method)
    {
        foreach (array('Only', 'Except', 'On') as $type) {
            if ($this->{"filterFails{$type}"}($filter, $request, $method)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the filter fails the "only" constraint.
     *
     * @param  array  $filter
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return bool
     */
    protected function filterFailsOnly($filter, $request, $method)
    {
        if (! isset($filter['options']['only'])) return false;

        return ! in_array($method, (array) $filter['options']['only']);
    }

    /**
     * Determine if the filter fails the "except" constraint.
     *
     * @param  array  $filter
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return bool
     */
    protected function filterFailsExcept($filter, $request, $method)
    {
        if (! isset($filter['options']['except'])) return false;

        return in_array($method, (array) $filter['options']['except']);
    }

    /**
     * Determine if the filter fails the "on" constraint.
     *
     * @param  array  $filter
     * @param  \Nova\Http\Request  $request
     * @param  string  $method
     * @return bool
     */
    protected function filterFailsOn($filter, $request, $method)
    {
        $on = array_get($filter, 'options.on');

        if (is_null($on)) return false;

        // If the "on" is a string, we will explode it on the pipe so you can set any
        // amount of methods on the filter constraints and it will still work like
        // you specified an array. Then we will check if the method is in array.
        if (is_string($on)) $on = explode('|', $on);

        return ! in_array(strtolower($request->getMethod()), $on);
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

        return $this->router->callRouteFilter($filter, $parameters, $route, $request);
    }

}
