<?php

namespace Nova\Routing;

use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Http\Exception\HttpResponseException;
use Nova\Routing\Matching\HostValidator;
use Nova\Routing\Matching\MethodValidator;
use Nova\Routing\Matching\SchemeValidator;
use Nova\Routing\Matching\UriValidator;
use Nova\Routing\RouteCompiler;
use Nova\Routing\RouteDependencyResolverTrait;
use Nova\Support\Arr;
use Nova\Support\Str;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Closure;
use ReflectionFunction;


class Route
{
    use RouteDependencyResolverTrait;

    /**
     * The URI pattern the route responds to.
     *
     * @var string
     */
    protected $uri;

    /**
     * The HTTP methods the route responds to.
     *
     * @var array
     */
    protected $methods;

    /**
     * The route action array.
     *
     * @var array
     */
    protected $action;

    /**
     * The Controller method.
     *
     * @var mixed
     */
    protected $method;

    /**
     * The Controller instance.
     *
     * @var mixed
     */
    protected $controller;

    /**
     * The default values for the route.
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * The regular expression requirements.
     *
     * @var array
     */
    protected $wheres = array();

    /**
     * The array of matched parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * The parameter names for the route.
     *
     * @var array|null
     */
    protected $parameterNames;

    /**
     * The compiled version of the route.
     *
     * @var \Symfony\Component\Routing\CompiledRoute
     */
    protected $compiled;

    /**
     * The computed gathered middleware.
     *
     * @var array|null
     */
    protected $computedMiddleware;

    /**
     * The container instance used by the route.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The validators used by the routes.
     *
     * @var array
     */
    protected static $validators;

    /**
     * The processing order.
     *
     * @var int
     */
    protected $order = 0;


    /**
     * Create a new Route instance.
     *
     * @param  array   $methods
     * @param  string  $uri
     * @param  \Closure|array  $action
     * @return void
     */
    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;

        $this->methods = (array) $methods;

        $this->action = $action;

        if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }

        if (! is_null($prefix = Arr::get($this->action, 'prefix'))) {
            $this->prefix($prefix);
        }

        if (! is_null($order = Arr::get($this->action, 'order'))) {
            $this->order = (int) $order;
        }
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    public function run()
    {
        if (! isset($this->container)) {
            $this->container = new Container();
        }

        try {
            if (! $this->isControllerAction()) {
                return $this->runCallable();
            }

            return $this->runController();
        }
        catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Checks whether the route's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        $parameters = $this->resolveMethodDependencies(
            $this->parametersWithoutNulls(), new ReflectionFunction($callable)
        );

        return call_user_func_array($callable, $parameters);
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this, $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the dispatcher for the route's controller.
     *
     * @return \Nova\Routing\ControllerDispatcher
     */
    public function controllerDispatcher()
    {
        if ($this->container->bound('routing.controller.dispatcher')) {
            return $this->container['routing.controller.dispatcher'];
        }

        return new ControllerDispatcher($this->container);
    }

    /**
     * Get the controller instance for the route.
     *
     * @return mixed
     */
    public function getController()
    {
        if (! isset($this->controller)) {
            list ($controller, $this->method) = $this->parseControllerCallback();

            return $this->controller = $this->container->make($controller);
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
     *
     * @return string
     */
    public function getControllerMethod()
    {
        if (! isset($this->method)) {
            list (, $method) = $this->parseControllerCallback();

            return $this->method = $method;
        }

        return $this->method;
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    /**
     * Determine if the route matches given request.
     *
     * @param  \Nova\Http\Request  $request
     * @param  bool  $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute();

        foreach ($this->getValidators() as $validator) {
            if (! $includingMethod && ($validator instanceof MethodValidator)) {
                continue;
            }

            if (! $validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile the route into a Symfony CompiledRoute instance.
     *
     * @return void
     */
    protected function compileRoute()
    {
        if (! $this->compiled) {
            return $this->compiled = with(new RouteCompiler($this))->compile();
        }

        return $this->compiled;
    }

    /**
     * Get all middleware, including the ones from the controller.
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = array();

        return $this->computedMiddleware = array_unique(array_merge(
            $this->middleware(), $this->controllerMiddleware()

        ), SORT_REGULAR);
    }

    /**
     * Get or set the middlewares attached to the route.
     *
     * @param  array|string|null $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return $this->getMiddleware();
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            $this->getMiddleware(), $middleware
        );

        return $this;
    }

    /**
     * Get the middlewares attached to the route.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return (array) Arr::get($this->action, 'middleware', array());
    }

    /**
     * Get the middleware for the route's controller.
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (! $this->isControllerAction()) {
            return array();
        }

        return ControllerDispatcher::getMiddleware(
            $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get a given parameter from the route.
     *
     * @param  string  $name
     * @param  mixed   $default
     * @return string
     */
    public function getParameter($name, $default = null)
    {
        return $this->parameter($name, $default);
    }

    /**
     * Get a given parameter from the route.
     *
     * @param  string  $name
     * @param  mixed   $default
     * @return string
     */
    public function parameter($name, $default = null)
    {
        return Arr::get($this->parameters(), $name, $default);
    }

    /**
     * Set a parameter to the given value.
     *
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     */
    public function setParameter($name, $value)
    {
        $this->parameters();

        $this->parameters[$name] = $value;
    }

    /**
     * Unset a parameter on the route if it is set.
     *
     * @param  string  $name
     * @return void
     */
    public function forgetParameter($name)
    {
        $this->parameters();

        unset($this->parameters[$name]);
    }

    /**
     * Get the key / value list of parameters for the route.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function parameters()
    {
        if (! isset($this->parameters)) {
            throw new \LogicException("Route is not bound.");
        }

        return array_map(function ($value)
        {
            return is_string($value) ? rawurldecode($value) : $value;

        }, $this->parameters);
    }

    /**
     * Get the key / value list of parameters without null values.
     *
     * @return array
     */
    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(), function ($parameter)
        {
            return ! is_null($parameter);
        });
    }

    /**
     * Get all of the parameter names for the route.
     *
     * @return array
     */
    public function parameterNames()
    {
        if (isset($this->parameterNames)) {
            return $this->parameterNames;
        }

        return $this->parameterNames = $this->compileParameterNames();
    }

    /**
     * Get the parameter names for the route.
     *
     * @return array
     */
    protected function compileParameterNames()
    {
        preg_match_all('/\{(.*?)\}/', $this->domain() .$this->uri, $matches);

        return array_map(function ($match)
        {
            return trim($match, '?');

        }, $matches[1]);
    }

    /**
     * Bind the route to a given request for execution.
     *
     * @param  \Nova\Http\Request  $request
     * @return $this
     */
    public function bind(Request $request)
    {
        $this->compileRoute();

        $this->bindParameters($request);

        return $this;
    }

    /**
     * Extract the parameter list from the request.
     *
     * @param  \Nova\Http\Request  $request
     * @return array
     */
    public function bindParameters(Request $request)
    {
        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        $params = $this->matchToKeys(
            array_slice($this->bindPathParameters($request), 1)
        );

        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        if (! is_null($this->compiled->getHostRegex())) {
            $params = $this->bindHostParameters($request, $params);
        }

        return $this->parameters = $this->replaceDefaults($params);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @param  \Nova\Http\Request  $request
     * @return array
     */
    protected function bindPathParameters(Request $request)
    {
        preg_match($this->compiled->getRegex(), '/' .$request->decodedPath(), $matches);

        return $matches;
    }

    /**
     * Extract the parameter list from the host part of the request.
     *
     * @param  \Nova\Http\Request  $request
     * @param  array  $parameters
     * @return array
     */
    protected function bindHostParameters(Request $request, $parameters)
    {
        preg_match($this->compiled->getHostRegex(), $request->getHost(), $matches);

        return array_merge($this->matchToKeys(array_slice($matches, 1)), $parameters);
    }

    /**
     * Combine a set of parameter matches with the route's keys.
     *
     * @param  array  $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        $parameterNames = $this->parameterNames();

        if (count($parameterNames) == 0) {
            return array();
        }

        $parameters = array_intersect_key($matches, array_flip($parameterNames));

        return array_filter($parameters, function ($value)
        {
            return is_string($value) && (strlen($value) > 0);
        });
    }

    /**
     * Replace null parameters with their defaults.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => &$value) {
            if (! isset($value)) {
                $value = Arr::get($this->defaults, $key);
            }
        }

        return $parameters;
    }

    /**
     * Get the route validators for the instance.
     *
     * @return array
     */
    public static function getValidators()
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }

        // To match the route, we will use a chain of responsibility pattern with the
        // validator implementations. We will spin through each one making sure it
        // passes and then we will know if the route as a whole matches request.
        return static::$validators = array(
            new UriValidator(),
            new MethodValidator(),
            new SchemeValidator(),
            new HostValidator(),
        );
    }

    /**
     * Sort the given array of Route instances by their order.
     *
     * @param  array  $routes
     * @return array
     */
    public static function sortByOrder(array $routes)
    {
        usort($routes, function ($a, $b)
        {
            if ($a->order == $b->order) {
                return strcmp($a->uri, $b->uri);
            }

            return ($a->order < $b->order) ? -1 : 1;
        });

        return $routes;
    }

    /**
     * Set/Get the processing order for the route.
     *
     * @param  int|null  $order
     * @return $this
     */
    public function order($order = null)
    {
        if (is_null($order)) {
            return $this->order;
        }

        $this->order = (int) $order;

        return $this;
    }

    /**
     * Set a default value for the route.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function defaults($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Get the regular expression requirements on the route.
     *
     * @return array
     */
    public function patterns()
    {
        return $this->wheres;
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return $this
     */
    public function where($name, $expression = null)
    {
        foreach ($this->parseWhere($name, $expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    /**
     * Parse arguments to the where method into an array.
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return array
     */
    protected function parseWhere($name, $expression)
    {
        return is_array($name) ? $name : array($name => $expression);
    }

    /**
     * Add a prefix to the route URI.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->uri = trim($prefix, '/') .'/' .trim($this->uri, '/');

        return $this;
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->uri();
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods();
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * Determine if the route only responds to HTTP requests.
     *
     * @return bool
     */
    public function httpOnly()
    {
        return in_array('http', $this->action, true);
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function httpsOnly()
    {
        return $this->secure();
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function secure()
    {
        return in_array('https', $this->action, true);
    }

    /**
     * Get the domain defined for the route.
     *
     * @return string|null
     */
    public function domain()
    {
        if (isset($this->action['domain'])) {
            return $this->action['domain'];
        }
    }

    /**
     * Get the URI that the route responds to.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the URI that the route responds to.
     *
     * @param  string  $uri
     * @return \Nova\Routing\Route
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get the prefix of the route instance.
     *
     * @return string
     */
    public function getPrefix()
    {
        if (isset($this->action['prefix'])) {
            return $this->action['prefix'];
        }
    }

    /**
     * Get the name of the route instance.
     *
     * @return string
     */
    public function getName()
    {
        if (isset($this->action['as'])) {
            return $this->action['as'];
        }
    }

    /**
     * Get the action name for the route.
     *
     * @return string
     */
    public function getActionName()
    {
        return isset($this->action['controller']) ? $this->action['controller'] : 'Closure';
    }

    /**
     * Get the action array for the route.
     *
     * @return array
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the action array for the route.
     *
     * @param  array  $action
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the compiled version of the route.
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function getCompiled()
    {
        return $this->compiled;
    }

    /**
     * Set the container instance on the route.
     *
     * @param  \Nova\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the router instance on the route.
     *
     * @param  \Nova\Routing\Router  $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Dynamically access route parameters.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->parameter($key);
    }
}
