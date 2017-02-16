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

        $response = $pipeline->send($request)
            ->through($middleware)
            ->then(function ($request) use ($instance, $route, $method)
            {
                return $this->router->prepareResponse(
                    $request, $this->call($instance, $route, $method)
                );
            });

        return $response;
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
}
