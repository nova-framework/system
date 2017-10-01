<?php

namespace Nova\Pipeline;

use Nova\Container\Container;
use Nova\Pipeline\Contracts\PipelineInterface;

use Closure;
use RuntimeException;


class Pipeline implements PipelineInterface
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
        $pipeline = array_reduce(array_reverse($this->pipes), function ($stack, $pipe)
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

        // The pipe is not a Closure instance.
        else if (! is_object($pipe)) {
            list($name, $parameters) = $this->parsePipeString($pipe);

            $pipe = $this->getContainer()->make($name);

            $parameters = array_merge(array($passable, $stack), $parameters);
        } else {
            $parameters = array($passable, $stack);
        }

        return call_user_func_array(array($pipe, $this->method), $parameters);
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, array());

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return array($name, $parameters);
    }

    /**
     * Get the container instance.
     *
     * @return \Nova\Container\Container
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! isset($this->container)) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }
}
