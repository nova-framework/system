<?php

namespace Nova\Foundation;

use Nova\Config\FileEnvironmentVariablesLoader;
use Nova\Config\FileLoader;
use Nova\Container\Container;
use Nova\Events\EventServiceProvider;
use Nova\Filesystem\Filesystem;
use Nova\Foundation\Http\Kernel;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Routing\RoutingServiceProvider;
use Nova\Support\Facades\Facade;
use Nova\Support\ServiceProvider;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Closure;
use RuntimeException;


class Application extends Container
{
	/**
	 * The Nova framework version.
	 *
	 * @var string
	 */
	const VERSION = '4.0.0';

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $hasBeenBootstrapped = false;

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
	 * The application namespace.
	 *
	 * @var string
	 */
	protected $namespace = null;


	/**
	 * Create a new Nova application instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->registerBaseBindings();

		$this->registerBaseServiceProviders();

		$this->registerCoreContainerAliases();
	}

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version()
	{
		return static::VERSION;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function registerBaseBindings()
	{
		static::setInstance($this);

		$this->instance('app', $this);

		$this->instance('Nova\Container\Container', $this);
	}

	/**
	 * Register all of the base service providers.
	 *
	 * @return void
	 */
	protected function registerBaseServiceProviders()
	{
		$this->register(new EventServiceProvider($this));

		$this->register(new RoutingServiceProvider($this));
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
	 * Register the event service provider.
	 *
	 * @return void
	 */
	protected function registerEventProvider()
	{
		$this->register(new EventServiceProvider($this));
	}

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * @param  array  $bootstrappers
	 * @return void
	 */
	public function bootstrapWith(array $bootstrappers)
	{
		$this->hasBeenBootstrapped = true;

		foreach ($bootstrappers as $bootstrapper) {
			$this->make($bootstrapper)->bootstrap($this);
		}
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function hasBeenBootstrapped()
	{
		return $this->hasBeenBootstrapped;
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

		// Here we will bind the install paths into the container as strings that can be
		// accessed from any point in the system. Each path key is prefixed with path
		// so that they have the consistent naming convention inside the container.
		foreach (array_except($paths, array('app')) as $key => $value) {
			$this->instance("path.{$key}", realpath($value));
		}
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

		return $this['env'] = with(new EnvironmentDetector())->detect($envs, $args);
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
	 * Register all of the configured providers..
	 *
	 * @return void
	 */
	public function registerConfiguredProviders()
	{
		$config = $this->make('config');

		$files = new Filesystem;

		with(new ProviderRepository($this, $files, $config['app.manifest']))
			->load($config['app.providers']);
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

		// If the given "provider" is a string, we will resolve it, passing in the
		// application instance automatically for the developer. This is simply
		// a more convenient way of specifying your service provider classes.
		if (is_string($provider)) {
			$provider = $this->resolveProviderClass($provider);
		}

		$provider->register();

		// Once we have registered the service we will iterate through the options
		// and set each of them on the application so they will be available on
		// the actual loading of the service objects and for developer usage.
		foreach ($options as $key => $value) {
			$this[$key] = $value;
		}

		$this->markAsRegistered($provider);

		// If the application has already booted, we will call this boot method on
		// the provider class so it has an opportunity to do its boot logic and
		// will be ready for any usage by the developer's application logics.
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
		$this['events']->fire($class = get_class($provider), array($provider));

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
		// We will simply spin through each of the deferred providers and register each
		// one and boot them if the application has booted. This should make each of
		// the remaining services available to this application for immediate use.
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

		// If the service provider has not already been loaded and registered we can
		// register it with the application and remove the service from this list
		// of deferred services, since it will already be loaded on subsequent.
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
		// Once the provider that provides the deferred service has been registered we
		// will remove it from our local list of the deferred services with related
		// providers so that this container does not try to resolve it out again.
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
		// Once the application has booted we will also fire some "booted" callbacks
		// for any listeners that need to do work after this initial booting gets
		// finished. This is useful when ordering the boot-up processes we run.

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

		if ($this->isBooted()) $this->fireAppCallbacks(array($callback));
	}

	/**
	 * Determine if middleware has been disabled for the application.
	 *
	 * @return bool
	 */
	public function shouldSkipMiddleware()
	{
		return $this->bound('middleware.disable') &&
			   ($this->make('middleware.disable') === true);
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
	 * @param  int	 $code
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
		$files = new Filesystem();

		return new FileLoader($files, $this['path'] .DS .'Config');
	}

	/**
	 * Get the environment variables loader instance.
	 *
	 * @return \Nova\Config\EnvironmentVariablesLoaderInterface
	 */
	public function getEnvironmentVariablesLoader()
	{
		$files = new Filesystem();

		return new FileEnvironmentVariablesLoader($files, $this['path.base']);
	}

	/**
	 * Get the service provider repository instance.
	 *
	 * @return \Nova\Foundation\ProviderRepository
	 */
	public function getProviderRepository()
	{
		$files = new Filesystem();

		$manifest = $this['config']['app.manifest'];

		return new ProviderRepository($files, $manifest, $this->runningInConsole());
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

		$this['events']->fire('locale.changed', array($locale));
	}

	/**
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	public function registerCoreContainerAliases()
	{
		$aliases = array(
			'app'				=> 'Nova\Foundation\Application',
			'forge'				=> 'Nova\Console\Application',
			'auth'				=> 'Nova\Auth\AuthManager',
			'cache'				=> 'Nova\Cache\CacheManager',
			'cache.store'		=> 'Nova\Cache\Repository',
			'template.compiler'	=> 'Nova\View\Compilers\TemplateCompiler',
			'config'			=> 'Nova\Config\Repository',
			'cookie'			=> 'Nova\Cookie\CookieJar',
			'encrypter'			=> 'Nova\Encryption\Encrypter',
			'db'				=> 'Nova\Database\DatabaseManager',
			'db.connection'		=> array('Nova\Database\Connection', 'Nova\Database\Contracts\ConnectionInterface'),
			'events'			=> 'Nova\Events\Dispatcher',
			'files'				=> 'Nova\Filesystem\Filesystem',
			'hash'				=> 'Nova\Hashing\HasherInterface',
			'log'				=> array('Nova\Log\Writer', 'Psr\Log\LoggerInterface'),
			'language'			=> 'Nova\Language\LanguageManager',
			'mailer'			=> 'Nova\Mail\Mailer',
			'paginator'			=> 'Nova\Pagination\Factory',
			'auth.reminder'		=> 'Nova\Auth\Reminders\PasswordBroker',
			'queue'				=> 'Nova\Queue\QueueManager',
			'queue.connection'	=> 'Nova\Queue\Queue',
			'redirect'			=> 'Nova\Routing\Redirector',
			'request'			=> 'Nova\Http\Request',
			'router'			=> 'Nova\Routing\Router',
			'session'			=> 'Nova\Session\SessionManager',
			'session.store'		=> 'Nova\Session\Store',
			'url'				=> 'Nova\Routing\UrlGenerator',
			'validator'			=> 'Nova\Validation\Factory',
			'view'				=> 'Nova\View\Factory',
		);

		foreach ($aliases as $key => $aliases) {
			foreach ((array) $aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush()
	{
		parent::flush();

		$this->loadedProviders = array();
	}

	/**
	 * Get the used kernel object.
	 *
	 * @return \Nova\Console\Contracts\KernelInterface|\Nova\Http\Contracts\KernelInterface
	 */
	protected function getKernel()
	{
		$kernelInterface = $this->runningInConsole()
			? 'Nova\Console\Contracts\KernelInterface'
			: 'Nova\Http\Contracts\KernelInterface';

		return $this->make($kernelInterface);
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
