<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Broadcasters\LogBroadcaster;
use Nova\Broadcasting\Broadcasters\NullBroadcaster;
use Nova\Broadcasting\Broadcasters\RedisBroadcaster;
use Nova\Broadcasting\Broadcasters\PusherBroadcaster;
use Nova\Broadcasting\Contracts\FactoryInterface;
use Nova\Broadcasting\Contracts\ShouldBroadcastNowInterface;
use Nova\Broadcasting\PendingBroadcast;
use Nova\Support\Arr;

use Pusher;

use Closure;
use InvalidArgumentException;


class BroadcastManager implements FactoryInterface
{
	/**
	 * The application instance.
	 *
	 * @var \Nova\Foundation\Application
	 */
	protected $app;

	/**
	 * The array of resolved broadcast drivers.
	 *
	 * @var array
	 */
	protected $drivers = array();

	/**
	 * The registered custom driver creators.
	 *
	 * @var array
	 */
	protected $customCreators = array();


	/**
	 * Create a new manager instance.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Register the routes for handling broadcast authentication and sockets.
	 *
	 * @param  array|null  $attributes
	 * @return void
	 */
	public function routes(array $attributes = null)
	{
		$attributes = $attributes ?: array('middleware' => array('web'));

		$this->app['router']->group($attributes, function ($router)
		{
			$router->post('broadcasting/auth', 'Nova\Broadcasting\BroadcastController@authenticate');
		});
	}

	/**
	 * Get the socket ID for the given request.
	 *
	 * @param  \Nova\Http\Request|null  $request
	 * @return string|null
	 */
	public function socket($request = null)
	{
		if (is_null($request) && ! $this->app->bound('request')) {
			return;
		}

		$request = $request ?: $this->app['request'];

		if ($request->hasHeader('X-Socket-ID')) {
			return $request->header('X-Socket-ID');
		}
	}

	/**
	 * Begin broadcasting an event.
	 *
	 * @param  mixed|null  $event
	 * @return \Nova\Broadcasting\PendingBroadcast|void
	 */
	public function event($event = null)
	{
		return new PendingBroadcast($this->app->make('events'), $event);
	}

	/**
	 * Queue the given event for broadcast.
	 *
	 * @param  mixed  $event
	 * @return void
	 */
	public function queue($event)
	{
		$connection = ($event instanceof ShouldBroadcastNowInterface) ? 'sync' : null;

		if (is_null($connection) && isset($event->connection)) {
			$connection = $event->connection;
		}

		$queue = null;

		if (isset($event->broadcastQueue)) {
			$queue = $event->broadcastQueue;
		} else if (isset($event->queue)) {
			$queue = $event->queue;
		}

		$this->app->make('queue')->connection($connection)->pushOn(
			$queue, 'Nova\Broadcasting\BroadcastEvent', array('event' => serialize(clone $event))
		);
	}

	/**
	 * Get a driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 */
	public function connection($driver = null)
	{
		return $this->driver($driver);
	}

	/**
	 * Get a driver instance.
	 *
	 * @param  string  $name
	 * @return mixed
	 */
	public function driver($name = null)
	{
		$name = $name ?: $this->getDefaultDriver();

		return $this->drivers[$name] = $this->get($name);
	}

	/**
	 * Attempt to get the connection from the local cache.
	 *
	 * @param  string  $name
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 */
	protected function get($name)
	{
		return isset($this->drivers[$name]) ? $this->drivers[$name] : $this->resolve($name);
	}

	/**
	 * Resolve the given store.
	 *
	 * @param  string  $name
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolve($name)
	{
		$config = $this->getConfig($name);

		if (is_null($config)) {
			throw new InvalidArgumentException("Broadcaster [{$name}] is not defined.");
		}

		$driver = $config['driver'];

		if (isset($this->customCreators[$driver])) {
			return $this->callCustomCreator($config);
		} else {
			$driverMethod = 'create'.ucfirst($driver) .'Driver';

			if (method_exists($this, $driverMethod)) {
				return call_user_func(array($this, $driverMethod), $config);
			} else {
				throw new InvalidArgumentException("Driver [{$driver}] not supported.");
			}
		}
	}

	/**
	 * Call a custom driver creator.
	 *
	 * @param  array  $config
	 * @return mixed
	 */
	protected function callCustomCreator(array $config)
	{
		$driver = $config['driver'];

		return call_user_func($this->customCreators[$driver], $this->app, $config);
	}

	/**
	 * Create an instance of the driver.
	 *
	 * @param  array  $config
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 */
	protected function createPusherDriver(array $config)
	{
		return new PusherBroadcaster(
			new Pusher($config['key'], $config['secret'], $config['app_id'], Arr::get($config, 'options', array()))
		);
	}

	/**
	 * Create an instance of the driver.
	 *
	 * @param  array  $config
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 */
	protected function createRedisDriver(array $config)
	{
		return new RedisBroadcaster(
			$this->app->make('redis'), Arr::get($config, 'connection')
		);
	}

	/**
	 * Create an instance of the driver.
	 *
	 * @param  array  $config
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 */
	protected function createLogDriver(array $config)
	{
		return new LogBroadcaster(
			$this->app->make('Psr\Log\LoggerInterface')
		);
	}

	/**
	 * Create an instance of the driver.
	 *
	 * @param  array  $config
	 * @return \Nova\Broadcasting\Contracts\BroadcasterInterface
	 */
	protected function createNullDriver(array $config)
	{
		return new NullBroadcaster();
	}

	/**
	 * Get the connection configuration.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		return $this->app['config']["broadcasting.connections.{$name}"];
	}

	/**
	 * Get the default driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['broadcasting.default'];
	}

	/**
	 * Set the default driver name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultDriver($name)
	{
		$this->app['config']['broadcasting.default'] = $name;
	}

	/**
	 * Register a custom driver creator Closure.
	 *
	 * @param  string	$driver
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function extend($driver, Closure $callback)
	{
		$this->customCreators[$driver] = $callback;

		return $this;
	}

	/**
	 * Dynamically call the default driver instance.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$instance = $this->driver();

		return call_user_func_array(array($instance, $method), $parameters);
	}
}
