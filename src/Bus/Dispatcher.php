<?php

namespace Nova\Bus;


use Nova\Container\Container;
use Nova\Foundation\Pipeline;
use Nova\Queue\QueueInterface;
use Nova\Queue\ShouldQueueInterface;
use Nova\Bus\QueueingDispatcherInterface;

use Closure;
use RuntimeException;


class Dispatcher implements QueueingDispatcherInterface
{
    /**
     * The container implementation.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The pipes to send commands through before dispatching.
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The command to handler mapping for non-self-handling events.
     *
     * @var array
     */
    protected $handlers = array();

    /**
     * The queue resolver callback.
     *
     * @var \Closure|null
     */
    protected $queueResolver;


    /**
     * Create a new command dispatcher instance.
     *
     * @param  \Nova\Container\Container  $container
     * @param  \Closure|null  $queueResolver
     * @return void
     */
    public function __construct(Container $container, Closure $queueResolver = null)
    {
        $this->container = $container;

        $this->queueResolver = $queueResolver;
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        if (! is_null($this->queueResolver) && $this->commandShouldBeQueued($command)) {
            return $this->dispatchToQueue($command);
        } else {
            return $this->dispatchNow($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        if (! is_null($handler) || ! is_null($handler = $this->getCommandHandler($command))) {
            $callback = function ($command) use ($handler)
            {
                return $handler->handle($command);
            };
        }

        // The command is self handling.
        else {
            $callback = function ($command)
            {
                return $this->container->call(array($command, 'handle'));
            };
        }

        $pipeline = new Pipeline($this->container, $this->pipes);

        return $pipeline->handle($command, $callback);
    }

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        $key = get_class($command);

        return array_key_exists($key, $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command)
    {
        $key = get_class($command);

        if (array_key_exists($key, $this->handlers)) {
            $handler = $this->handlers[$key];

            return $this->container->make($handler);
        }
    }

    /**
     * Determine if the given command should be queued.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function commandShouldBeQueued($command)
    {
        return ($command instanceof ShouldQueueInterface);
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function dispatchToQueue($command)
    {
        $connection = isset($command->connection) ? $command->connection : null;

        $queue = call_user_func($this->queueResolver, $connection);

        if (! $queue instanceof QueueInterface) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        } else {
            return $this->pushCommandToQueue($queue, $command);
        }
    }

    /**
     * Push the command onto the given queue instance.
     *
     * @param  \Nova\Queue\Contracts\QueueInterface  $queue
     * @param  mixed  $command
     * @return mixed
     */
    protected function pushCommandToQueue($queue, $command)
    {
        if (isset($command->queue, $command->delay)) {
            return $queue->laterOn($command->queue, $command->delay, $command);
        }

        if (isset($command->queue)) {
            return $queue->pushOn($command->queue, $command);
        }

        if (isset($command->delay)) {
            return $queue->later($command->delay, $command);
        }

        return $queue->push($command);
    }

    /**
     * Set the pipes through which commands should be piped before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Map a command to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }
}
