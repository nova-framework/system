<?php

namespace Nova\Auth;

use Nova\Auth\Contracts\UserProviderInterface;
use Nova\Auth\DatabaseUserProvider;
use Nova\Auth\ExtendedUserProvider;
use Nova\Auth\RequestGuard;
use Nova\Auth\SessionGuard;
use Nova\Auth\TokenGuard;
use Nova\Foundation\Application;

use Closure;
use InvalidArgumentException;


class AuthManager
{
	/**
	 * The application instance.
	 *
	 * @var \Nova\Foundation\Application
	 */
	protected $app;

	/**
	 * The registered custom driver creators.
	 *
	 * @var array
	 */
	protected $customCreators = array();

	/**
	 * The registered custom provider creators.
	 *
	 * @var array
	 */
	protected $customProviderCreators = array();

	/**
	 * The array of created "drivers".
	 *
	 * @var array
	 */
	protected $guards = array();

	/**
	 * The user resolver shared by various services.
	 *
	 * Determines the default user for Request, and the UserInterface.
	 *
	 * @var \Closure
	 */
	protected $userResolver;


	/**
	 * Create a new manager instance.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;

		$this->userResolver = function ($guard = null)
		{
			return $this->guard($guard)->user();
		};
	}

	/**
	 * Attempt to get the guard from the local cache.
	 *
	 * @param  string  $name
	 * @return \Nova\Auth\Guard
	 */
	public function guard($name = null)
	{
		$name = $name ?: $this->getDefaultDriver();

		if (! isset($this->guards[$name])) {
			$this->guards[$name] = $this->resolve($name);
		}

		return $this->guards[$name];
	}

	/**
	 * Resolve the given guard.
	 *
	 * @param  string  $name
	 * @return \Nova\Auth\Guard
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolve($name)
	{
		$config = $this->getConfig($name);

		if (is_null($config)) {
			throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
		}

		if (isset($this->customCreators[$config['driver']])) {
			return $this->callCustomCreator($name, $config);
		}

		$method = 'create' .ucfirst($config['driver']) .'Driver';

		if (! method_exists($this, $method)) {
			throw new InvalidArgumentException("Auth guard driver [{$config['driver']}] is not defined.");
		}

		return call_user_func(array($this, $method), $name, $config);
	}

	/**
	 * Call a custom driver creator.
	 *
	 * @param  string  $name
	 * @param  array  $config
	 * @return mixed
	 */
	protected function callCustomCreator($name, array $config)
	{
		$driver = $config['driver'];

		$callback = $this->customCreators[$driver];

		return call_user_func($callback, $this->app, $name, $config);
	}

	/**
	 * Create an instance of the database driver.
	 *
	 * @return \Nova\Auth\Guard
	 */
	public function createSessionDriver($name, array $config)
	{
		$provider = $this->createUserProvider($config['provider']);

		$guard = new SessionGuard($name, $provider, $this->app['session.store']);

		// When using the remember me functionality of the authentication services we
		// will need to be set the encryption instance of the guard, which allows
		// secure, encrypted cookie values to get generated for those cookies.
		if (method_exists($guard, 'setCookieJar')) {
			$guard->setCookieJar($this->app['cookie']);
		}

		if (method_exists($guard, 'setDispatcher')) {
			$guard->setDispatcher($this->app['events']);
		}

		if (method_exists($guard, 'setRequest')) {
			$guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
		}

		return $guard;
	}

	/**
	 * Create a token based authentication guard.
	 *
	 * @param  string  $name
	 * @param  array  $config
	 * @return \Nova\Auth\TokenGuard
	 */
	public function createTokenDriver($name, $config)
	{
		// The token guard implements a basic API token based guard implementation
		// that takes an API token field from the request and matches it to the
		// user in the database or another persistence layer where users are.
		$guard = new TokenGuard(
			$this->createUserProvider($config['provider']),
			$this->app['request']
		);

		$this->app->refresh('request', $guard, 'setRequest');

		return $guard;
	}

	/**
	 * Create the user provider implementation for the driver.
	 *
	 * @param  string  $provider
	 * @return \Nova\Auth\UserProviderInterface
	 *
	 * @throws \InvalidArgumentException
	 */
	public function createUserProvider($provider)
	{
		$config = $this->app['config']["auth.providers.{$provider}"];

		// Retrieve the driver from configuration.
		$driver = $config['driver'];

		if (isset($this->customProviderCreators[$driver])) {
			$callback = $this->customProviderCreators[$driver];

			return call_user_func($callback, $this->app, $config);
		}

		switch ($driver) {
			case 'database':
				return $this->createDatabaseProvider($config);

			case 'extended':
				return $this->createExtendedProvider($config);

			default:
				break;
		}

		throw new InvalidArgumentException("Authentication user provider [{$driver}] is not defined.");
	}

	/**
	 * Create an instance of the database user provider.
	 *
	 * @return \Nova\Auth\DatabaseUserProvider
	 */
	protected function createDatabaseProvider(array $config)
	{
		$connection = $this->app['db']->connection();

		return new DatabaseUserProvider($connection, $this->app['hash'], $config['table']);
	}

	/**
	 * Create an instance of the Extended user provider.
	 *
	 * @return \Nova\Auth\ExtendedUserProvider
	 */
	protected function createExtendedProvider(array $config)
	{
		return new ExtendedUserProvider($this->app['hash'], $config['model']);
	}

	/**
	 * Get the guard configuration.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		return $this->app['config']["auth.guards.{$name}"];
	}

	/**
	 * Get the default authentication driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['auth.defaults.guard'];
	}

	/**
	 * Set the default guard driver the factory should serve.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function shouldUse($name)
	{
		$this->setDefaultDriver($name);

		$this->userResolver = function ($name = null)
		{
			return $this->guard($name)->user();
		};
	}

	/**
	 * Set the default authentication driver name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultDriver($name)
	{
		$this->app['config']['auth.defaults.guard'] = $name;
	}

	/**
	 * Register a new callback based request guard.
	 *
	 * @param  string  $driver
	 * @param  callable  $callback
	 * @return $this
	 */
	public function viaRequest($driver, callable $callback)
	{
		return $this->extend($driver, function () use ($callback)
		{
			$guard = new RequestGuard($callback, $this->app['request']);

			$this->app->refresh('request', $guard, 'setRequest');

			return $guard;
		});
	}

	/**
	 * Get the user resolver callback.
	 *
	 * @return \Closure
	 */
	public function userResolver()
	{
		return $this->userResolver;
	}

	/**
	 * Set the callback to be used to resolve users.
	 *
	 * @param  \Closure  $userResolver
	 * @return $this
	 */
	public function resolveUsersUsing(Closure $userResolver)
	{
		$this->userResolver = $userResolver;

		return $this;
	}

	/**
	 * Register a custom driver creator Closure.
	 *
	 * @param  string  $driver
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function extend($driver, Closure $callback)
	{
		$this->customCreators[$driver] = $callback;

		return $this;
	}

	/**
	 * Register a custom provider creator Closure.
	 *
	 * @param  string  $name
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function provider($name, Closure $callback)
	{
		$this->customProviderCreators[$name] = $callback;

		return $this;
	}

	/**
	 * Dynamically call the default driver instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->guard(), $method), $parameters);
	}
}
