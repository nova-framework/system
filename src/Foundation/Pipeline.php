<?php

namespace Nova\Foundation;

use Nova\Container\Container;

use Closure;
use RuntimeException;


class Pipeline
{
    /**
     * The container implementation.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';


    /**
     * Create a new class instance.
     *
     * @param  \Mini\Container\Container  $container
     * @param  mixed|array  $pipes
     * @param  string|null  $method
     * @return void
     */
    public function __construct(Container $container, $pipes = array(), $method = null)
    {
        $this->container = $container;

        $this->pipes = is_array($pipes) ? $pipes : array($pipes);

        if (! is_null($method)) {
            $this->method = $method;
        }
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  mixed  $passable
     * @param  \Closure  $callback
     * @return mixed
     */
    public function handle($passable, Closure $callback)
    {
        $pipes = array_reverse($this->pipes);

        $pipeline = array_reduce($pipes, function ($stack, $pipe)
        {
            return $this->createSlice($stack, $pipe);

        }, $this->prepareDestination($callback));

        return call_user_func($pipeline, $passable);
    }

    /**
     * Get the initial slice to begin the stack call.
     *
     * @param  \Closure  $callback
     * @return \Closure
     */
    protected function prepareDestination(Closure $callback)
    {
        return function ($passable) use ($callback)
        {
            return call_user_func($callback, $passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function createSlice($stack, $pipe)
    {
        return function ($passable) use ($stack, $pipe)
        {
            return $this->call($pipe, $passable, $stack);
        };
    }

    /**
     * Call the pipe Closure or the method 'handle' in its class instance.
     *
     * @param  mixed  $pipe
     * @param  mixed  $passable
     * @param  \Closure  $stack
     * @return \Closure
     * @throws \BadMethodCallException
     */
    protected function call($pipe, $passable, $stack)
    {
        if ($pipe instanceof Closure) {
            return call_user_func($pipe, $passable, $stack);
        }

        $parameters = array($passable, $stack);

        if (is_string($pipe)) {
            list ($name, $payload) = array_pad(explode(':', $pipe, 2), 2, null);

            if (! empty($payload)) {
                $parameters = array_merge($parameters, explode(',', $payload));
            }

            $pipe = $this->container->make($name);
        }

        // The pipes must be either a Closure, a string or an object instance.
        else if (! is_object($pipe)) {
            throw new RuntimeException('An invalid pipe has been passed to the Pipeline.');
        }

        return call_user_func_array(array($pipe, $this->method), $parameters);
    }
}
