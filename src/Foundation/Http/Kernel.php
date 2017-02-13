<?php

namespace Nova\Foundation\Http;

use Nova\Http\Contracts\KernelInterface;
use Nova\Foundation\Application;
use Nova\Pipeline\Pipeline;
use Nova\Routing\Router;
use Nova\Support\Facades\Facade;

use Closure;


class Kernel implements KernelInterface
{
    /**
     * The Application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * The Router instance.
     *
     * @var \Routing\Router
     */
    protected $router;

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = array();

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = array();


    /**
     * Create a new HTTP kernel instance.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;

        $this->router = $router;

        foreach($this->routeMiddleware as $name => $middleware) {
            $this->router->middleware($name, $middleware);
        }
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    public function handle($request)
    {
        try {
            $request->enableHttpMethodParameterOverride();

            $response = $this->sendRequestThroughRouter($request);
        }
        catch (\Exception $e) {
            if ($this->app->runningUnitTests()) throw $e;

            return $this->app['exception']->handleException($e);
        }
        catch (\Throwable $e) {
            if ($this->app->runningUnitTests()) throw $e;

            return $this->app['exception']->handleException($e);
        }

        $this->app['events']->fire('kernel.handled', array($request, $response));

        return $response;
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $shouldSkipMiddleware = $this->app->shouldSkipMiddleware();

        // Refresh the Request and boot the Application.
        $this->app->instance('request', $request);

        Facade::clearResolvedInstance('request');

        $this->bootstrap();

        //
        $middleware = $shouldSkipMiddleware ? array() : $this->middleware;

        $pipeline = new Pipeline($this->app);

        return $pipeline->send($request)
            ->through($middleware)
            ->then($this->dispatchToRouter());
    }

    /**
     * Call the terminate method on any terminable middleware.
     *
     * @param  \Nova\Http\Request  $request
     * @param  \Nova\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $shouldSkipMiddleware = $this->app->shouldSkipMiddleware();

        //
        $middlewares = $shouldSkipMiddleware ? array() : array_merge(
            $this->gatherRouteMiddlewares($request),
            $this->middleware
        );

        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Closure) continue;

            list($name, $parameters) = $this->parseMiddleware($middleware);

            $instance = $this->app->make($name);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }

        $this->app->terminate($request, $response);
    }

    /**
     * Gather the route middleware for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function gatherRouteMiddlewares($request)
    {
        if (is_null($route = $request->route())) {
            return array();
        }

        $middlewares = $this->router->gatherRouteMiddlewares($route);

        return array_filter($middlewares, function ($middleware)
        {
            return is_string($middleware);
        });
    }

    /**
     * Parse a middleware string to get the name and parameters.
     *
     * @param  string  $middleware
     * @return array
     */
    protected function parseMiddleware($middleware)
    {
        list($name, $parameters) = array_pad(explode(':', $middleware, 2), 2, array());

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return array($name, $parameters);
    }

    /**
     * Bootstrap the application for HTTP requests.
     *
     * @return void
     */
    public function bootstrap()
    {
        $this->app->boot();
    }

    /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request)
        {
            $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }

    /**
     * Add a new middleware to beginning of the stack if it does not already exist.
     *
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            array_unshift($this->middleware, $middleware);
        }

        return $this;
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @param  string|\Closure  $middleware
     * @return \Nova\Foundation\Http\Kernel
     */
    public function pushMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            array_push($this->middleware, $middleware);
        }

        return $this;
    }

    /**
     * Determine if the kernel has a given middleware.
     *
     * @param  string  $middleware
     * @return bool
     */
    public function hasMiddleware($middleware)
    {
        return in_array($middleware, $this->middleware);
    }

    /**
     * Get the Nova application instance.
     *
     * @return \Nova\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
