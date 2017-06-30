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
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middlewareGroups = array();

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
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = array(
		'Nova\Foundation\Bootstrap\LoadConfiguration',
		'Nova\Foundation\Bootstrap\HandleExceptions',
		'Nova\Foundation\Bootstrap\BootApplication',
	);


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

		foreach ($this->middlewareGroups as $key => $middleware) {
			$router->middlewareGroup($key, $middleware);
		}

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
		catch (Exception $e) {
			$response = $this->handleException($request, $e);
		}
		catch (Throwable $e) {
			$response = $this->handleException($request, new FatalThrowableError($e));
		}

		$this->app['events']->fire('kernel.handled', array($request, $response));

		return $response;
	}

	/**
	 * Send the given request through the middleware / router.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\Response
	 */
	protected function sendRequestThroughRouter($request)
	{
		$this->app->instance('request', $request);

		Facade::clearResolvedInstance('request');

		$this->bootstrap();

		//
		$shouldSkipMiddleware = $this->app->shouldSkipMiddleware();

		$pipeline = new Pipeline($this->app);

		return $pipeline->send($request)->through($shouldSkipMiddleware ? array() : $this->middleware)->then(function ($request)
		{
			$this->app->instance('request', $request);

			return $this->router->dispatch($request);
		});
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

		$middlewares = $shouldSkipMiddleware ? array() : array_merge(
			$this->gatherRouteMiddleware($request),
			$this->middleware
		);

		foreach ($middlewares as $middleware) {
			if (! is_string($middleware)) {
				continue;
			}

			list($name, $parameters) = $this->parseMiddleware($middleware);

			$instance = $this->app->make($name);

			if (method_exists($instance, 'terminate')) {
				$instance->terminate($request, $response);
			}
		}

		$this->app->terminate();
	}

	/**
	 * Gather the route middleware for the given request.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return array
	 */
	protected function gatherRouteMiddleware($request)
	{
		if (! is_null($route = $request->route())) {
			return $this->router->gatherRouteMiddleware($route);
		}

		return array();
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
		if (! $this->app->hasBeenBootstrapped()) {
			$this->app->bootstrapWith($this->bootstrappers);
		}
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
	 * Determines whether middleware should be skipped during request.
	 *
	 * @return bool
	 */
	protected function shouldSkipMiddleware()
	{
		return $this->app->shouldSkipMiddleware();
	}

	/**
	 * Handle the given exception.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function handleException($request, Exception $e)
	{
		$this->reportException($e);

		return $this->renderException($request, $e);
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function reportException(Exception $e)
	{
		$this->getExceptionHandler()->report($e);
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function renderException($request, Exception $e)
	{
		return $this->getExceptionHandler()->render($request, $e);
	}

	/**
	 * Get the Nova application instance.
	 *
	 * @return \Nova\Foundation\Contracts\ExceptionHandlerInterface
	 */
	public function getExceptionHandler()
	{
		return $this->app->make('Nova\Foundation\Contracts\ExceptionHandlerInterface');
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
