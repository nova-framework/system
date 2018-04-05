<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Broadcasters\LogBroadcaster;
use Nova\Broadcasting\Broadcasters\NullBroadcaster;
use Nova\Broadcasting\Broadcasters\RedisBroadcaster;
use Nova\Broadcasting\Broadcasters\PusherBroadcaster;
use Nova\Broadcasting\Broadcasters\QuasarBroadcaster;
use Nova\Broadcasting\FactoryInterface;
use Nova\Broadcasting\PendingBroadcast;
use Nova\Support\Arr;

use Closure;
use InvalidArgumentException;

use Pusher\Pusher;


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

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Nova\Broadcasting\BroadcasterInterface
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
        }

        $method = 'create' .ucfirst($driver) .'Driver';

        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method), $config);
        }

        throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
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
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function createQuasarDriver(array $config)
    {
        return new QuasarBroadcaster($this->app, $config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function createPusherDriver(array $config)
    {
        $options = Arr::get($config, 'options', array());

        // Create a Pusher instance.
        $pusher = new Pusher($config['key'], $config['secret'], $config['app_id'], $options);

        return new PusherBroadcaster($this->app, $pusher);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function createRedisDriver(array $config)
    {
        $connection = Arr::get($config, 'connection');

        // Create a Redis Database instance.
        $redis = $this->app->make('redis');

        return new RedisBroadcaster($this->app, $redis, $connection);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function createLogDriver(array $config)
    {
        $logger = $this->app->make('Psr\Log\LoggerInterface');

        return new LogBroadcaster($this->app, $logger);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function createNullDriver(array $config)
    {
        return new NullBroadcaster($this->app);
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
     * @param  string    $driver
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
        return call_user_func_array([$this->driver(), $method], $parameters);
    }
}
