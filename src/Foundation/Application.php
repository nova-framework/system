<?php

namespace Nova\Foundation;

use Closure;

use Stack\Builder;

use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Config\FileLoader;
use Nova\Container\Container;
use Nova\Filesystem\Filesystem;
use Nova\Pipeline\Pipeline;
use Nova\Support\Facades\Facade;
use Nova\Support\ServiceProvider;
use Nova\Events\EventServiceProvider;
use Nova\Log\LogServiceProvider;
use Nova\Routing\RoutingServiceProvider;
use Nova\Exception\ExceptionServiceProvider;
use Nova\Config\FileEnvironmentVariablesLoader;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

use Nova\Support\Contracts\ResponsePreparerInterface;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Exception;
use Throwable;


class Application extends Container implements ResponsePreparerInterface
{
    /**
     * The Nova framework version.
     *
     * @var string
     */
    const VERSION = '4.0.17';

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = array();

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = array();

    /**
     * The array of finish callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = array();

    /**
     * All of the developer defined middlewares.
     *
     * @var array
     */
    protected $middlewares = array();

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = array();

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = array();

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = array();

    /**
     * The request class used by the application.
     *
     * @var string
     */
    protected static $requestClass = 'Nova\Http\Request';

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace = null;


    /**
     * Create a new Nova application instance.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request = null)
    {
        $this->registerBaseBindings($request ?: $this->createNewRequest());

        $this->registerBaseServiceProviders();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public static function version()
    {
        return static::VERSION;
    }

    /**
     * Create a new request instance from the request class.
     *
     * @return \Nova\Http\Request
     */
    protected function createNewRequest()
    {
        return forward_static_call(array(static::$requestClass, 'createFromGlobals'));
    }

    /**
     * Register the basic bindings into the container.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    protected function registerBaseBindings($request)
    {
        $this->instance('request', $request);

        $this->instance('Nova\Container\Container', $this);
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        foreach (array('Event', 'Log', 'Exception', 'Routing') as $name) {
            $this->{"register{$name}Provider"}();
        }
    }

    /**
     * Register the event service provider.
     *
     * @return void
     */
    protected function registerEventProvider()
    {
        $this->register(new EventServiceProvider($this));
    }

    /**
     * Register the log service provider.
     *
     * @return void
     */
    protected function registerLogProvider()
    {
        $this->register(new LogServiceProvider($this));
    }

    /**
     * Register the exception service provider.
     *
     * @return void
     */
    protected function registerExceptionProvider()
    {
        $this->register(new ExceptionServiceProvider($this));
    }

    /**
     * Register the routing service provider.
     *
     * @return void
     */
    protected function registerRoutingProvider()
    {
        $this->register(new RoutingServiceProvider($this));
    }

    /**
     * Bind the installation paths to the application.
     *
     * @param  array  $paths
     * @return void
     */
    public function bindInstallPaths(array $paths)
    {
        $this->instance('path', realpath($paths['app']));

        foreach (array_except($paths, array('app')) as $key => $value) {
            $this->instance("path.{$key}", realpath($value));
        }
    }

    /**
     * Start the exception handling for the request.
     *
     * @return void
     */
    public function startExceptionHandling()
    {
        $this['exception']->register($this->environment());

        //
        $debug = $this['config']['app.debug'];

        $this['exception']->setDebug($debug);
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     * @return string
     */
    public function environment()
    {
        if (count(func_get_args()) > 0) {
            return in_array($this['env'], func_get_args());
        }

        return $this['env'];
    }

    /**
     * Determine if application is in local environment.
     *
     * @return bool
     */
    public function isLocal()
    {
        return $this['env'] == 'local';
    }

    /**
     * Detect the application's current environment.
     *
     * @param  array|string  $envs
     * @return string
     */
    public function detectEnvironment($envs)
    {
        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;

        return $this['env'] = (new EnvironmentDetector())->detect($envs, $args);
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Determine if we are running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this['env'] == 'testing';
    }

    /**
     * Force register a service provider with the application.
     *
     * @param  \Nova\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @return \Nova\Support\ServiceProvider
     */
    public function forceRegister($provider, $options = array())
    {
        return $this->register($provider, $options, true);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Nova\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return \Nova\Support\ServiceProvider
     */
    public function register($provider, $options = array(), $force = false)
    {
        if ($registered = $this->getRegistered($provider) && ! $force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Nova\Support\ServiceProvider|string  $provider
     * @return \Nova\Support\ServiceProvider|null
     */
    public function getRegistered($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        if (array_key_exists($name, $this->loadedProviders)) {
            return array_first($this->serviceProviders, function($key, $value) use ($name)
            {
                return get_class($value) == $name;
            });
        }
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Nova\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Nova\Support\ServiceProvider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this['events']->dispatch($class = get_class($provider), array($provider));

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = array();
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
    protected function loadDeferredProvider($service)
    {
        $provider = $this->deferredServices[$service];

        if (! isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        if ($service) unset($this->deferredServices[$service]);

        $this->register($instance = new $provider($this));

        if (! $this->booted) {
            $this->booting(function() use ($instance)
            {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = array())
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * (Overriding Container::bound)
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * (Overriding Container::extend)
     *
     * @param  string   $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::extend($abstract, $closure);
    }

    /**
     * Register a function for determining when to use array sessions.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function useArraySessions(Closure $callback)
    {
        $this->bind('session.reject', function() use ($callback)
        {
            return $callback;
        });
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->serviceProviders, function($provider)
        {
            $this->bootProvider($provider);
        });

        $this->bootApplication();
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Nova\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call(array($provider, 'boot'));
        }
    }

    /**
     * Boot the application and fire app callbacks.
     *
     * @return void
     */
    protected function bootApplication()
    {
        $this->fireAppCallbacks($this->bootingCallbacks);

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks(array($callback));
        }
    }

    /**
     * Run the application and send the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function run(SymfonyRequest $request = null)
    {
        $request = $request ?: $this['request'];

        // Setup the Router middlewares.
        $middlewareGroups = $this['config']->get('app.middlewareGroups');

        foreach ($middlewareGroups as $key => $middleware) {
            $this['router']->middlewareGroup($key, $middleware);
        }

        $routeMiddleware = $this['config']->get('app.routeMiddleware');

        foreach($routeMiddleware as $name => $middleware) {
            $this['router']->middleware($name, $middleware);
        }

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

        $response->send();

        $this->shutdown($request, $response);
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->refreshRequest($request = Request::createFromBase($request));

        $this->boot();

        // Create a Pipeline instance.
        $pipeline = new Pipeline(
            $this, $this->shouldSkipMiddleware() ? array() : $this->middleware
        );

        return $pipeline->handle($request, function ($request)
        {
            $this->instance('request', $request);

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
    public function shutdown($request, $response)
    {
        $middlewares = $this->shouldSkipMiddleware() ? array() : array_merge(
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

        $this->terminate();
    }

    /**
     * Register a terminating callback with the application.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function terminating(Closure $callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Call the "terminating" callbacks assigned to the application.
    *
     * @return void
     */
    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $callback) {
            call_user_func($callback);
        }
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
        return $this->make('Nova\Foundation\Contracts\ExceptionHandlerInterface');
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
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware()
    {
        return $this->bound('middleware.disable') && ($this->make('middleware.disable') === true);
    }

    /**
     * Add the middleware.
     *
     * @param  array  $middlewares
     * @return \Nova\Foundation\Application
     */
    public function middleware(array $middleware)
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Add a new middleware to beginning of the stack if it does not already exist.
     *
     * @param  string  $middleware
     * @return \Nova\Foundation\Application
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
     * @return \Nova\Foundation\Application
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
     * Refresh the bound request instance in the container.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    protected function refreshRequest(Request $request)
    {
        $this->instance('request', $request);

        Facade::clearResolvedInstance('request');
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  array  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * Prepare the given value as a Response object.
     *
     * @param  mixed  $value
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function prepareResponse($value)
    {
        if (! $value instanceof SymfonyResponse) {
            $value = new Response($value);
        }

        return $value->prepare($this['request']);
    }

    /**
     * Determine if the application is ready for responses.
     *
     * @return bool
     */
    public function readyForResponses()
    {
        return $this->booted;
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return file_exists($this['path.storage'] .DS .'down');
    }

    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function abort($code, $message = '', array $headers = array())
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Get the configuration loader instance.
     *
     * @return \Nova\Config\LoaderInterface
     */
    public function getConfigLoader()
    {
        return new FileLoader(new Filesystem, $this['path'] .DS .'Config');
    }

    /**
     * Get the environment variables loader instance.
     *
     * @return \Nova\Config\EnvironmentVariablesLoaderInterface
     */
    public function getEnvironmentVariablesLoader()
    {
        return new FileEnvironmentVariablesLoader(new Filesystem, $this['path.base']);
    }

    /**
     * Get the service provider repository instance.
     *
     * @return \Nova\Foundation\ProviderRepository
     */
    public function getProviderRepository()
    {
        $manifest = $this['config']['app.manifest'];

        return new ProviderRepository(new Filesystem, $manifest);
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Get or set the request class for the application.
     *
     * @param  string  $class
     * @return string
     */
    public static function requestClass($class = null)
    {
        if (! is_null($class)) static::$requestClass = $class;

        return static::$requestClass;
    }

    /**
     * Set the application request for the console environment.
     *
     * @return void
     */
    public function setRequestForConsoleEnvironment()
    {
        $url = $this['config']->get('app.url', 'http://localhost');

        $parameters = array($url, 'GET', array(), array(), array(), $_SERVER);

        $this->refreshRequest(static::onRequest('create', $parameters));
    }

    /**
     * Call a method on the default request class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function onRequest($method, $parameters = array())
    {
        return forward_static_call_array(array(static::requestClass(), $method), $parameters);
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }

    /**
     * Set the current application locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);

        $this['language']->setLocale($locale);

        $this['events']->dispatch('locale.changed', array($locale));
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        $aliases = array(
            'app'            => 'Nova\Foundation\Application',
            'forge'          => 'Nova\Console\Application',
            'auth'           => 'Nova\Auth\AuthManager',
            'cache'          => 'Nova\Cache\CacheManager',
            'cache.store'    => 'Nova\Cache\Repository',
            'config'         => 'Nova\Config\Repository',
            'cookie'         => 'Nova\Cookie\CookieJar',
            'encrypter'      => 'Nova\Encryption\Encrypter',
            'db'             => 'Nova\Database\DatabaseManager',
            'events'         => 'Nova\Events\Dispatcher',
            'files'          => 'Nova\Filesystem\Filesystem',
            'hash'           => 'Nova\Hashing\HasherInterface',
            'language'       => 'Nova\Language\LanguageManager',
            'log'            => array('Nova\Log\Writer', 'Psr\Log\LoggerInterface'),
            'mailer'         => 'Nova\Mail\Mailer',
            'paginator'      => 'Nova\Pagination\Environment',
            'redirect'       => 'Nova\Routing\Redirector',
            'request'        => 'Nova\Http\Request',
            'router'         => 'Nova\Routing\Router',
            'session'        => 'Nova\Session\SessionManager',
            'session.store'  => 'Nova\Session\Store',
            'url'            => 'Nova\Routing\UrlGenerator',
            'validator'      => 'Nova\Validation\Factory',
            'view'           => 'Nova\View\Factory',
        );

        foreach ($aliases as $key => $value) {
            foreach ((array) $value as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $filePath = base_path('composer.json');

        $composer = json_decode(file_get_contents($filePath), true);

        //
        $appPath = realpath(app_path());

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if ($appPath == realpath(base_path() .DS .$pathChoice)) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }
}
