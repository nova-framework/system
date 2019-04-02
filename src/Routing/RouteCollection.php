<?php

namespace Nova\Routing;

use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Support\Arr;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use Countable;
use ArrayIterator;
use IteratorAggregate;


class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * An array of the routes keyed by method.
     *
     * @var array
     */
    protected $routes = array(
        'GET'     => array(),
        'POST'    => array(),
        'PUT'     => array(),
        'DELETE'  => array(),
        'PATCH'   => array(),
        'HEAD'    => array(),
        'OPTIONS' => array(),
    );

    /**
     * An flattened array of all of the routes.
     *
     * @var array
     */
    protected $allRoutes = array();

    /**
     * A look-up table of routes by their names.
     *
     * @var array
     */
    protected $nameList = array();

    /**
     * A look-up table of routes by controller action.
     *
     * @var array
     */
    protected $actionList = array();

    /**
     * Add a Route instance to the collection.
     *
     * @param  \Nova\Routing\Route  $route
     * @return \Nova\Routing\Route
     */
    public function add(Route $route)
    {
        $this->addToCollections($route);

        $this->addLookups($route);

        return $route;
    }

    /**
     * Add the given route to the arrays of routes.
     *
     * @param  \Nova\Routing\Route  $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->domain() .$route->getUri();

        foreach ($route->methods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $key = $method .$domainAndUri;

        $this->allRoutes[$key] = $route;
    }

    /**
     * Add the route to any look-up tables if necessary.
     *
     * @param  \Nova\Routing\Route  $route
     * @return void
     */
    protected function addLookups($route)
    {
        // If the route has a name, we will add it to the name look-up table so that we
        // will quickly be able to find any route associate with a name and not have
        // to iterate through every route every time we need to perform a look-up.
        $action = $route->getAction();

        if (isset($action['as'])) {
            $name = $action['as'];

            $this->nameList[$name] = $route;
        }

        // When the route is routing to a controller we will also store the action that
        // is used by the route. This will let us reverse route to controllers while
        // processing a request and easily generate URLs to the given controllers.
        if (isset($action['controller'])) {
            $this->addToActionList($action, $route);
        }
    }

    /**
     * Add a route to the controller action dictionary.
     *
     * @param  array  $action
     * @param  \Nova\Routing\Route  $route
     * @return void
     */
    protected function addToActionList($action, $route)
    {
        $controller = $action['controller'];

        if (! isset($this->actionList[$controller])) {
            $this->actionList[$controller] = $route;
        }
    }

    /**
     * Find the first route matching a given request.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        // First, we will see if we can find a matching route for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for routes with another verb.

        if (is_null($route = $this->fastCheck($routes, $request))) {
            $route = $this->check($routes, $request);
        }

        if (! is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found, we will check if a matching route is specified on
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.

        if (! empty($others = $this->checkForAlternateVerbs($request))) {
            return $this->getOtherMethodsRoute($request, $others);
        }

        throw new NotFoundHttpException;
    }

    /**
     * Determine if any routes match on another HTTP verb.
     *
     * @param  \Nova\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(
            Router::$verbs, (array) $request->getMethod()
        );

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.

        return array_filter($methods, function ($method) use ($request)
        {
            $route = $this->check($this->get($method), $request, false);

            return ! is_null($route);
        });
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
     *
     * @param  \Nova\Http\Request  $request
     * @param  array  $others
     * @return \Nova\Routing\Route
     *
     * @throws \Symfony\Component\Routing\Exception\MethodNotAllowedHttpException
     */
    protected function getOtherMethodsRoute($request, array $others)
    {
        if ($request->method() !== 'OPTIONS') {
            throw new MethodNotAllowedHttpException($others);
        }

        $route = new Route('OPTIONS', $request->path(), function () use ($others)
        {
            return new Response('', 200, array('Allow' => implode(',', $others)));
        });

        return $route->bind($request);
    }

    /**
     * Determine if a route in the array matches the request.
     *
     * @param  array  $routes
     * @param  \Nova\http\Request  $request
     * @param  bool  $includingMethod
     * @return \Nova\Routing\Route|null
     */
    protected function check(array $routes, $request, $includingMethod = true)
    {
        return Arr::first($routes, function ($key, $route) use ($request, $includingMethod)
        {
            return $route->matches($request, $includingMethod);
        });
    }

    /**
     * Determine if a route in the array fully matches the request - the fast way.
     *
     * @param  array  $routes
     * @param  \Nova\http\Request  $request
     * @return \Nova\Routing\Route|null
     */
    protected function fastCheck(array $routes, $request)
    {
        $domain = $request->getHost();

        $path = ($request->path() == '/') ? '/' : '/' .$request->path();

        foreach (array($domain .$path, $path) as $key) {
            if (is_null($route = Arr::get($routes, $key))) {
                continue;
            }

            // We will do a full matching on the found route.
            else if ($route->matches($request, true)) {
                return $route;
            }
        }
    }

    /**
     * Get all of the routes in the collection.
     *
     * @param  string|null  $method
     * @return array
     */
    protected function get($method = null)
    {
        if (is_null($method)) {
            return $this->getRoutes();
        }

        return Arr::get($this->routes, $method, array());
    }

    /**
     * Determine if the route collection contains a given named route.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return ! is_null($this->getByName($name));
    }

    /**
     * Get a route instance by its name.
     *
     * @param  string  $name
     * @return \Nova\Routing\Route|null
     */
    public function getByName($name)
    {
        if (isset($this->nameList[$name])) {
            return $this->nameList[$name];
        }
    }

    /**
     * Get a route instance by its controller action.
     *
     * @param  string  $action
     * @return \Nova\Routing\Route|null
     */
    public function getByAction($action)
    {
        if (isset($this->actionList[$action])) {
            return $this->actionList[$action];
        }
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return array
     */
    public function getRoutes()
    {
        return array_values($this->allRoutes);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->getRoutes());
    }

}
