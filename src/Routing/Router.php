<?php
/**
 * Router - routing urls to closures and controllers.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Events\Dispatcher;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Routing\ControllerDispatcher;
use Nova\Routing\ControllerInspector;
use Nova\Routing\RouteCollection;
use Nova\Routing\RouteFiltererInterface;
use Nova\Routing\Route;
use Nova\Support\Str;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use BadMethodCallException;
use Closure;


/**
 * Router class will load requested Controller / Closure based on URL.
 */
class Router implements HttpKernelInterface, RouteFiltererInterface
{
    /**
     * The event dispatcher instance.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
     *
     * @var \Nova\Routing\RouteCollection
     */
    protected $routes;

    /**
     * Matched Route, the current found Route, if any.
     *
     * @var Route|null $current
     */
    protected $current = null;

    /**
     * The request currently being dispatched.
     *
     * @var \Http\Request
     */
    protected $currentRequest;

    /**
     * The controller dispatcher instance.
     *
     * @var \Nova\Routing\ControllerDispatcher
     */
    protected $controllerDispatcher;

    /**
     * The asset file dispatcher instance.
     *
     * @var \Nova\Routing\AssetFileDispatcher
     */
    protected $fileDispatcher;

    /**
     * The controller inspector instance.
     *
     * @var \Nova\Routing\ControllerInspector
     */
    protected $inspector;

    /**
     * Indicates if the router is running filters.
     *
     * @var bool
     */
    protected $filtering = true;

    /**
     * The registered pattern based filters.
     *
     * @var array
     */
    protected $patternFilters = array();

    /**
     * The registered regular expression based filters.
     *
     * @var array
     */
    protected $regexFilters = array();

    /**
     * The registered route value binders.
     *
     * @var array
     */
    protected $binders = array();

    /**
     * The globally available parameter patterns.
     *
     * @var array
     */
    protected $patterns = array();

    /**
     * Array of Route Groups
     *
     * @var array $groupStack
     */
    private $groupStack = array();

    /**
     * An array of HTTP request Methods.
     *
     * @var array $methods
     */
    public static $methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS');

    /**
     * The resource registrar instance.
     *
     * @var \Nova\Routing\ResourceRegistrar
     */
    protected $registrar;


    /**
     * Router constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct(Dispatcher $events = null, Container $container = null)
    {
        $this->events = $events;

        $this->routes = new RouteCollection();

        $this->container = $container ?: new Container();

        //
        $this->bind('_missing', function($value)
        {
            return explode('/', $value);
        });
    }

    /**
     * Register a new GET route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function get($route, $action)
    {
        return $this->addRoute(array('GET', 'HEAD'), $route, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function post($route, $action)
    {
        return $this->addRoute('POST', $route, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function put($route, $action)
    {
        return $this->addRoute('PUT', $route, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function patch($route, $action)
    {
        return $this->addRoute('PATCH', $route, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function delete($route, $action)
    {
        return $this->addRoute('DELETE', $route, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function options($route, $action)
    {
        return $this->addRoute('OPTIONS', $route, $action);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function any($route, $action)
    {
        $methods = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');

        return $this->addRoute($methods, $route, $action);
    }

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    public function match($methods, $route, $action)
    {
        $methods = array_map('strtoupper', (array) $methods);

        return $this->addRoute($methods, $route, $action);
    }

    /**
     * Register an array of controllers with wildcard routing.
     *
     * @param  array  $controllers
     * @return void
     */
    public function controllers(array $controllers)
    {
        foreach ($controllers as $uri => $name) {
            $this->controller($uri, $name);
        }
    }

    /**
     * Route a Controller to a URI with wildcard routing.
     *
     * @param  string  $uri
     * @param  string  $controller
     * @param  array   $names
     * @return void
     * @throws  \BadMethodCallException
     */
    public function controller($uri, $controller, $names = array())
    {
        $inspector = $this->getInspector();

        //
        $prepended = $controller;

        if (! empty($this->groupStack)) {
            $prepended = $this->prependGroupUses($controller);
        }

        // Retrieve the Controller routable methods and associated information.
        $routable = $inspector->getRoutable($prepended, $uri);

        foreach ($routable as $method => $routes) {
            foreach ($routes as $route) {
                $this->registerInspected($route, $controller, $method, $names);
            }
        }

        $this->addFallthroughRoute($controller, $uri);
    }

    /**
     * Register an inspected controller route.
     *
     * @param  array   $route
     * @param  string  $controller
     * @param  string  $method
     * @param  array   $names
     * @return void
     */
    protected function registerInspected($route, $controller, $method, &$names)
    {
        $action = array('uses' => $controller .'@' .$method);

        //
        $action['as'] = array_get($names, $method);

        $this->{$route['verb']}($route['uri'], $action);
    }

    /**
     * Add a fallthrough route for a controller.
     *
     * @param  string  $controller
     * @param  string  $uri
     * @return void
     * @throws  \BadMethodCallException
     */
    protected function addFallthroughRoute($controller, $uri)
    {
        $route = $this->any($uri .'/{_missing}', $controller .'@missingMethod');

        $route->where('_missing', '(.*)');
    }

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function resource($name, $controller, array $options = array())
    {
        $registrar = $this->getRegistrar();

        $registrar->register($name, $controller, $options);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param  array     $attributes
     * @param  \Closure  $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback)
    {
        $this->updateGroupStack($attributes);

        // Execute the group callback.
        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (! empty($this->groupStack)) {
            $old = last($this->groupStack);

            $attributes = static::mergeGroup($attributes, $old);
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param  array  $new
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        $old = last($this->groupStack);

        return static::mergeGroup($new, $old);
    }

    /**
     * Merge the given group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    public static function mergeGroup($new, $old)
    {
        $new['namespace'] = static::formatUsesPrefix($new, $old);

        $new['prefix'] = static::formatGroupPrefix($new, $old);

        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        $new['where'] = array_merge(
            isset($old['where']) ? $old['where'] : array(),
            isset($new['where']) ? $new['where'] : array()
        );

        if (isset($old['as'])) {
            $new['as'] = $old['as'] .(isset($new['as']) ? $new['as'] : '');
        }

        return array_merge_recursive(array_except($old, array('namespace', 'prefix', 'where', 'as')), $new);
    }

    /**
     * Format the uses prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string
     */
    protected static function formatUsesPrefix($new, $old)
    {
        if (isset($new['namespace']) && isset($old['namespace'])) {
            return trim(array_get($old, 'namespace'), '\\') .'\\' .trim($new['namespace'], '\\');
        } else if (isset($new['namespace'])) {
            return trim($new['namespace'], '\\');
        }

        return array_get($old, 'namespace');
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string
     */
    protected static function formatGroupPrefix($new, $old)
    {
        if (isset($new['prefix'])) {
            return trim(array_get($old, 'prefix'), '/') .'/' .trim($new['prefix'], '/');
        }

        return array_get($old, 'prefix');
    }

    /**
     * Get the prefix from the last group on the stack.
     *
     * @return string
     */
    public function getLastGroupPrefix()
    {
        if (! empty($this->groupStack)) {
            $last = end($this->groupStack);

            return isset($last['prefix']) ? $last['prefix'] : '';
        }

        return '';
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Nova\Routing\Route
     */
    protected function addRoute($methods, $route, $action = null)
    {
        $route = $this->createRoute($methods, $route, $action);

        // Add the current Route instance to the known Routes list.
        return $this->routes->add($route);
    }

    /**
     * Create a new route instance.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed   $action
     * @return \Nova\Routing\Route
     */
    protected function createRoute($methods, $uri, $action)
    {
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        // Prefix the current route pattern.
        $uri = $this->prefix($uri);

        $route = $this->newRoute($methods, $uri, $action);

        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed   $action
     * @return \Nova\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))->setContainer($this->container);
    }

    /**
     * Prefix the given URI with the last prefix.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prefix($uri)
    {
        $prefix = $this->getLastGroupPrefix();

        return trim(trim($prefix, '/') .'/' .trim($uri, '/'), '/') ?: '/';
    }

    /**
     * Add the necessary where clauses to the route based on its initial registration.
     *
     * @param  \Nova\Routing\Route  $route
     * @return \Nova\Routing\Route
     */
    protected function addWhereClausesToRoute($route)
    {
        $wheres = array_get($route->getAction(), 'where', array());

        $route->where(array_merge($this->patterns, $wheres));

        return $route;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \Nova\Routing\Route  $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $action = $this->mergeWithLastGroup($route->getAction());

        $route->setAction($action);
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array  $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if ($action instanceof Closure) {
            return false;
        }

        return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) $action = array('uses' => $action);

        if (! empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupUses($action['uses']);
        }

        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group uses onto the use clause.
     *
     * @param  string  $uses
     * @return string
     */
    protected function prependGroupUses($uses)
    {
        $group = last($this->groupStack);

        return isset($group['namespace']) ? $group['namespace'] .'\\' .$uses : $uses;
    }

    /**
     * Dispatch route
     * @return bool
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        // Asset Files Dispatching.
        $response = $this->dispatchToFile($request);

        if (! is_null($response)) return $response;

        // If no response was returned from the before filter, we will call the proper
        // route instance to get the response. If no route is found a response will
        // still get returned based on why no routes were found for this request.
        $response = $this->callFilter('before', $request);

        if (is_null($response)) {
            $response = $this->dispatchToRoute($request);
        }

        $response = $this->prepareResponse($request, $response);

        // Once this route has run and the response has been prepared, we will run the
        // after filter to do any last work on the response or for this application
        // before we will return the response back to the consuming code for use.
        $this->callFilter('after', $request, $response);

        return $response;
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    public function dispatchToRoute(Request $request)
    {
        // Execute the Routes matching.
        $route = $this->findRoute($request);

        $request->setRouteResolver(function() use ($route)
        {
            return $route;
        });

        $this->events->fire('router.matched', array($route, $request));

        // Once we have successfully matched the incoming request to a given route we
        // can call the before filters on that route. This works similar to global
        // filters in that if a response is returned we will not call the route.
        $response = $this->callRouteBefore($route, $request);

        if (is_null($response)) {
            $response = $route->run($request);
        }

        // Prepare the Reesponse.
        $response = $this->prepareResponse($request, $response);

        // After we have a prepared response from the route or filter we will call to
        // the "after" filters to do any last minute processing on this request or
        // response object before the response is returned back to the consumer.
        $this->callRouteAfter($route, $request, $response);

        return $response;
    }

    /**
     * Dispatch the request to a asset file and return the response.
     *
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    public function dispatchToFile(Request $request)
    {
        $fileDispatcher = $this->getFileDispatcher();

        return $fileDispatcher->dispatch($request);
    }

    /**
     * Find the route matching a given request.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Routing\Route
     */
    protected function findRoute(Request $request)
    {
        $this->current = $route = $this->routes->match($request);

        return $this->substituteBindings($route);
    }

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  \Nova\Routing\Route  $route
     * @return \Nova\Routing\Route
     */
    protected function substituteBindings($route)
    {
        foreach ($route->parameters() as $key => $value) {
            if (isset($this->binders[$key])) {
                $route->setParameter($key, $this->performBinding($key, $value, $route));
            }
        }

        return $route;
    }

    /**
     * Call the binding callback for the given key.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  \Nova\Routing\Route  $route
     * @return mixed
     */
    protected function performBinding($key, $value, $route)
    {
        return call_user_func($this->binders[$key], $value, $route);
    }

    /**
     * Register a route matched event listener.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function matched($callback)
    {
        $this->events->listen('router.matched', $callback);
    }

    /**
     * Register a new "before" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function before($callback)
    {
        $this->addGlobalFilter('before', $callback);
    }

    /**
     * Register a new "after" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function after($callback)
    {
        $this->addGlobalFilter('after', $callback);
    }

    /**
     * Register a new global filter with the router.
     *
     * @param  string  $filter
     * @param  string|callable   $callback
     * @return void
     */
    protected function addGlobalFilter($filter, $callback)
    {
        $this->events->listen('router.'.$filter, $this->parseFilter($callback));
    }

    /**
     * Register a new Filter with the Router.
     *
     * @param  string  $name
     * @param  string|callable  $callback
     * @return void
     */
    public function filter($name, $callback)
    {
        $this->events->listen('router.filter: '.$name, $this->parseFilter($callback));
    }

    /**
     * Parse the registered Filter.
     *
     * @param  callable|string  $callback
     * @return mixed
     */
    protected function parseFilter($callback)
    {
        if (is_string($callback) && ! Str::contains($callback, '@')) {
            return $callback .'@filter';
        }

        return $callback;
    }

    /**
     * Register a pattern-based filter with the router.
     *
     * @param  string  $pattern
     * @param  string  $name
     * @param  array|null  $methods
     * @return void
     */
    public function when($pattern, $name, $methods = null)
    {
        if (! is_null($methods)) {
            $methods = array_map('strtoupper', (array) $methods);
        }

        $this->patternFilters[$pattern][] = compact('name', 'methods');
    }

    /**
     * Register a regular expression based filter with the router.
     *
     * @param  string     $pattern
     * @param  string     $name
     * @param  array|null $methods
     * @return void
     */
    public function whenRegex($pattern, $name, $methods = null)
    {
        if (! is_null($methods)) {
            $methods = array_map('strtoupper', (array) $methods);
        }

        $this->regexFilters[$pattern][] = compact('name', 'methods');
    }

    /**
     * Register a Model binder for a wildcard.
     *
     * @param  string  $key
     * @param  string  $className
     * @param  \Closure  $callback
     * @return void
     *
     * @throws NotFoundHttpException
     */
    public function model($key, $className, Closure $callback = null)
    {
        $this->bind($key, function ($value) use ($className, $callback)
        {
            if (is_null($value)) {
                return null;
            }

            if ($model = with(new $className)->find($value)) {
                return $model;
            }

            if ($callback instanceof Closure) {
                return call_user_func($callback);
            }

            throw new NotFoundHttpException;
        });
    }

    /**
     * Add a new route parameter binder.
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        if (is_string($binder)) {
            $binder = $this->createClassBinding($binder);
        }

        $key = str_replace('-', '_', $key);

        $this->binders[$key] = $binder;
    }

    /**
     * Create a class based binding using the IoC container.
     *
     * @param  string    $binding
     * @return \Closure
     */
    public function createClassBinding($binding)
    {
        return function ($value, $route) use ($binding)
        {
            list ($className, $method) = array_pad(explode('@', $binding, 2), 2, 'bind');

            $instance = $this->container->make($className);

            return call_user_func(array($instance, $method), $value, $route);
        };
    }

    /**
     * Set a global where pattern on all routes
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Set a group of global where patterns on all routes
     *
     * @param  array  $patterns
     * @return void
     */
    public function patterns($patterns)
    {
        foreach ($patterns as $key => $pattern) {
            $this->pattern($key, $pattern);
        }
    }

    /**
     * Call the given filter with the request and response.
     *
     * @param  string  $filter
     * @param  \Nova\Http\Request   $request
     * @param  \Nova\Http\Response  $response
     * @return mixed
     */
    protected function callFilter($filter, $request, $response = null)
    {
        if (! $this->filtering) {
            return;
        }

        return $this->events->until('router.'.$filter, array($request, $response));
    }

    /**
     * Call the given route's before filters.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    public function callRouteBefore($route, $request)
    {
        $response = $this->callPatternFilters($route, $request);

        return $response ?: $this->callAttachedBefores($route, $request);
    }

    /**
     * Call the pattern based filters for the request.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return mixed|null
     */
    protected function callPatternFilters($route, $request)
    {
        foreach ($this->findPatternFilters($request) as $filter => $parameters) {
            $response = $this->callRouteFilter($filter, $parameters, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Find the patterned filters matching a request.
     *
     * @param  \Nova\Http\Request  $request
     * @return array
     */
    public function findPatternFilters($request)
    {
        $results = array();

        list($path, $method) = array($request->path(), $request->getMethod());

        foreach ($this->patternFilters as $pattern => $filters) {
            if (Str::is($pattern, $path)) {
                $merge = $this->patternsByMethod($method, $filters);

                $results = array_merge($results, $merge);
            }
        }

        foreach ($this->regexFilters as $pattern => $filters) {
            if (preg_match($pattern, $path)) {
                $merge = $this->patternsByMethod($method, $filters);

                $results = array_merge($results, $merge);
            }
        }

        return $results;
    }

    /**
     * Filter pattern filters that don't apply to the request verb.
     *
     * @param  string  $method
     * @param  array   $filters
     * @return array
     */
    protected function patternsByMethod($method, $filters)
    {
        $results = array();

        foreach ($filters as $filter) {
            if ($this->filterSupportsMethod($filter, $method)) {
                $name = $filter['name'];

                //
                $parsed = Route::parseFilters($name);

                $results = array_merge($results, $parsed);
            }
        }

        return $results;
    }

    /**
     * Determine if the given pattern filters applies to a given method.
     *
     * @param  array  $filter
     * @param  array  $method
     * @return bool
     */
    protected function filterSupportsMethod($filter, $method)
    {
        $methods = $filter['methods'];

        return is_null($methods) || in_array($method, $methods);
    }

    /**
     * Call the given route's before (non-pattern) filters.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    protected function callAttachedBefores($route, $request)
    {
        foreach ($route->beforeFilters() as $filter => $parameters) {
            $response = $this->callRouteFilter($filter, $parameters, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Call the given route's before filters.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @param  \Nova\Http\Response  $response
     * @return mixed
     */
    public function callRouteAfter($route, $request, $response)
    {
        foreach ($route->afterFilters() as $filter => $parameters) {
            $this->callRouteFilter($filter, $parameters, $route, $request, $response);
        }
    }

    /**
     * Call the given Route Filter.
     *
     * @param  string  $filter
     * @param  array  $parameters
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    public function callRouteFilter($filter, $parameters, $route, $request, $response = null)
    {
        if (! $this->filtering) {
            return;
        }

        $data = array_merge(array($route, $request, $response), $parameters);

        return $this->events->until('router.filter: '.$filter, $this->cleanFilterParameters($data));
    }

    /**
     * Clean the parameters being passed to a filter callback.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function cleanFilterParameters(array $parameters)
    {
        return array_filter($parameters, function ($parameter)
        {
            return ! is_null($parameter) && ($parameter !== '');
        });
    }

    /**
     * Run a callback with filters disable on the router.
     *
     * @param  callable  $callback
     * @return void
     */
    public function withoutFilters(callable $callback)
    {
        $this->disableFilters();

        call_user_func($callback);

        $this->enableFilters();
    }

    /**
     * Enable route filtering on the router.
     *
     * @return void
     */
    public function enableFilters()
    {
        $this->filtering = true;
    }

    /**
     * Disable route filtering on the router.
     *
     * @return void
     */
    public function disableFilters()
    {
        $this->filtering = false;
    }

    /**
     * Return the available Filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Create a response instance from the given value.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return \Nova\Http\Response
     */
    protected function prepareResponse($request, $response)
    {
        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        return $response->prepare($request);
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the router.
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get a route parameter for the current route.
     *
     * @param  string  $key
     * @param  string  $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        return $this->current()->parameter($key, $default);
    }

    /**
     * Return the current Matched Route, if there are any.
     *
     * @return null|Route
     */
    public function getCurrentRoute()
    {
        return $this->current();
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \Nova\Routing\Route
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a Route with the given name exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        return $this->routes->hasNamedRoute($name);
    }

    /**
     * Get the current route name.
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        return ($this->current()) ? $this->current()->getName() : null;
    }

    /**
     * Alias for the "currentRouteNamed" method.
     *
     * @param  mixed  string
     * @return bool
     */
    public function is()
    {
        $patterns = func_get_args();

        $name = $this->currentRouteName();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route matches a given name.
     *
     * @param  string  $name
     * @return bool
     */
    public function currentRouteNamed($name)
    {
        return ($this->current()) ? ($this->current()->getName() == $name) : false;
    }

    /**
     * Get the current route action.
     *
     * @return string|null
     */
    public function currentRouteAction()
    {
        if (is_null($route = $this->current())) {
            return;
        }

        $action = $route->getAction();

        return isset($action['controller']) ? $action['controller'] : null;
    }

    /**
     * Alias for the "currentRouteUses" method.
     *
     * @param  mixed  string
     * @return bool
     */
    public function uses()
    {
        $patterns = func_get_args();

        $action = $this->currentRouteAction();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route action matches a given action.
     *
     * @param  string  $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        return $this->currentRouteAction() == $action;
    }

    /**
     * Get the request currently being dispatched.
     *
     * @return \Nova\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Return the available Routes.
     *
     * @return \Nova\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get the controller dispatcher instance.
     *
     * @return \Nova\Routing\ControllerDispatcher
     */
    public function getControllerDispatcher()
    {
        if (isset($this->controllerDispatcher)) {
            return $this->controllerDispatcher;
        }

        return $this->controllerDispatcher = new ControllerDispatcher($this, $this->container);
    }

    /**
     * Set the controller dispatcher instance.
     *
     * @param  \Nova\Routing\ControllerDispatcher  $dispatcher
     * @return void
     */
    public function setControllerDispatcher(ControllerDispatcher $dispatcher)
    {
        $this->controllerDispatcher = $dispatcher;
    }

    /**
     * Get the controller dispatcher instance.
     *
     * @return \Nova\Routing\ControllerDispatcher
     */
    public function getFileDispatcher()
    {
        if (isset($this->fileDispatcher)) {
            return $this->fileDispatcher;
        }

        return $this->fileDispatcher = $this->container->make('Nova\Routing\Assets\DispatcherInterface');
    }

    /**
     * Get a Controller Inspector instance.
     *
     * @return \Nova\Routing\ControllerInspector
     */
    public function getInspector()
    {
        if (isset($this->inspector)) {
            return $this->inspector;
        }

        return $this->inspector = new ControllerInspector();
    }

    /**
     * Get a Resource Registrar instance.
     *
     * @return \Nova\Routing\ResourceRegistrar
     */
    public function getRegistrar()
    {
        if (isset($this->registrar)) {
            return $this->registrar;
        }

        return $this->registrar = new ResourceRegistrar($this);
    }

    /**
     * Get the response for a given request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  int   $type
     * @param  bool  $catch
     * @return \Nova\Http\Response
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return $this->dispatch(Request::createFromBase($request));
    }

}
