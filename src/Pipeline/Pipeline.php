<?php

namespace Nova\Pipeline;

use Nova\Container\Container;
use Nova\Pipeline\Contracts\PipelineInterface;

use Closure;


class Pipeline implements PipelineInterface
{
    /**
     * The container implementation.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

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
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipes = array_reverse($this->pipes);

        //
        $slice = $this->getInitialSlice($destination);

        foreach ($pipes as $pipe) {
            $slice = $this->getSlice($slice, $pipe);
        }

        return call_user_func($slice, $this->passable);
    }

    /**
     * Get the initial slice to begin the stack call.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function getInitialSlice(Closure $destination)
    {
        return function ($passable) use ($destination)
        {
            return call_user_func($destination, $passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function getSlice($stack, $pipe)
    {
        return function ($passable) use ($stack, $pipe)
        {
            return $this->callPipe($pipe, $passable, $stack);
        };
    }

    /**
     * Call a Closure or the method 'handle' in a class instance.
     *
     * @param  mixed  $pipe
     * @param  mixed  $passable
     * @param  \Closure  $stack
     * @return \Closure
     * @throws \BadMethodCallException
     */
    protected function callPipe($pipe, $passable, $stack)
    {
        if ($pipe instanceof Closure) {
            return call_user_func($pipe, $passable, $stack);
        }

        $parameters = array();

        if (! is_object($pipe)) {
            list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, array());

            if (is_string($parameters)) {
                $parameters = explode(',', $parameters);
            }

            $pipe = $this->container->make($name);
        }

        $parameters = array_merge(array($passable, $stack), $parameters);

        return call_user_func_array(array($pipe, $this->method), $parameters);
    }

}
