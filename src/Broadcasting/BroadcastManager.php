<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Broadcasters\LogBroadcaster;
use Nova\Broadcasting\Broadcasters\NullBroadcaster;
use Nova\Broadcasting\Broadcasters\RedisBroadcaster;
use Nova\Broadcasting\Broadcasters\PusherBroadcaster;
use Nova\Broadcasting\FactoryInterface;
use Nova\Support\Arr;

use Closure;
use InvalidArgumentException;

use Pusher;


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
     * @return \Nova\Broadcasting\BroadcasterInterface
     */
    protected function get($name)
    {
        return isset($this->drivers[$name]) ? $this->drivers[$name] : $this->resolve($name);
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

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($config);
            } else {
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
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
        return $this->customCreators[$config['driver']]($this->app, $config);
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

        return new PusherBroadcaster(
            new Pusher($config['key'], $config['secret'], $config['app_id'], $options)
        );
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

        return new RedisBroadcaster(
            $this->app->make('redis'), $connection
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Nova\Broadcasting\BroadcasterInterface
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
     * @return \Nova\Broadcasting\BroadcasterInterface
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
