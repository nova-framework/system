<?php

namespace Nova\Events;

use Nova\Broadcasting\ShouldBroadcastInterface;
use Nova\Broadcasting\ShouldBroadcastNowInterface;
use Nova\Container\Container;
use Nova\Events\DispatcherInterface;
use Nova\Support\Str;

use Exception;
use ReflectionClass;


class Dispatcher implements DispatcherInterface
{
    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * The wildcard listeners.
     *
     * @var array
     */
    protected $wildcards = array();

    /**
     * The sorted event listeners.
     *
     * @var array
     */
    protected $sorted = array();

    /**
     * The event firing stack.
     *
     * @var array
     */
    protected $firing = array();

    /**
     * The queue resolver instance.
     *
     * @var callable
     */
    protected $queueResolver;


    /**
     * Create a new event dispatcher instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container();
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed   $listener
     * @param  int     $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0)
    {
        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[$event][$priority][] = $this->makeListener($listener);

                unset($this->sorted[$event]);
            }
        }
    }

    /**
     * Setup a wildcard listener callback.
     *
     * @param  string  $event
     * @param  mixed   $listener
     * @return void
     */
    protected function setupWildcardListen($event, $listener)
    {
        $this->wildcards[$event][] = $this->makeListener($listener);
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) || isset($this->wildcards[$eventName]);
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param  string  $event
     * @param  array   $payload
     * @return void
     */
    public function push($event, $payload = array())
    {
        $this->listen($event .'_pushed', function() use ($event, $payload)
        {
            $this->dispatch($event, $payload);
        });
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $subscriber->subscribe($this);
    }

    /**
     * Resolve the subscriber instance.
     *
     * @param  mixed  $subscriber
     * @return mixed
     */
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param  string  $event
     * @param  array   $payload
     * @return mixed
     */
    public function until($event, $payload = array())
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Flush a set of queued events.
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        $this->dispatch($event .'_pushed');
    }

    /**
     * Get the event that is currently firing.
     *
     * @return string
     */
    public function firing()
    {
        return last($this->firing);
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string  $event
     * @param  mixed   $payload
     * @param  bool    $halt
     * @return array|null
     */
    public function dispatch($event, $payload = array(), $halt = false)
    {
        $responses = array();

        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        if (is_object($event)) {
            list($payload, $event) = array(array($event), get_class($event));
        }

        // If an array is not given to us as the payload, we will turn it into one so
        // we can easily use call_user_func_array on the listeners, passing in the
        // payload to each of them so that they receive each of these arguments.
        else if (! is_array($payload)) {
            $payload = array($payload);
        }

        $this->firing[] = $event;

        if (isset($payload[0]) && ($payload[0] instanceof ShouldBroadcastInterface)) {
            $this->broadcastEvent($payload[0]);
        }

        foreach ($this->getListeners($event) as $listener) {
            $response = call_user_func_array($listener, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (! is_null($response) && $halt) {
                array_pop($this->firing);

                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        array_pop($this->firing);

        return $halt ? null : $responses;
    }

    /**
     * Broadcast the given event class.
     *
     * @param  \Nova\Broadcasting\ShouldBroadcastInterface  $event
     * @return void
     */
    protected function broadcastEvent($event)
    {
        $connection = ($event instanceof ShouldBroadcastNowInterface) ? 'sync' : null;

        $queue = method_exists($event, 'onQueue') ? $event->onQueue() : null;

        $this->resolveQueue()->connection($connection)->pushOn($queue, 'Nova\Broadcasting\BroadcastEvent', array(
            'event' => serialize(clone $event),
        ));
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        $wildcards = $this->getWildcardListeners($eventName);

        if (! isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }

        return array_merge($this->sorted[$eventName], $wildcards);
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function getWildcardListeners($eventName)
    {
        $wildcards = array();

        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $wildcards;
    }

    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function sortListeners($eventName)
    {
        $this->sorted[$eventName] = array();

        // If listeners exist for the given event, we will sort them by the priority
        // so that we can call them in the correct order. We will cache off these
        // sorted event listeners so we do not have to re-sort on every events.
        if (isset($this->listeners[$eventName])) {
            krsort($this->listeners[$eventName]);

            $this->sorted[$eventName] = call_user_func_array(
                'array_merge', $this->listeners[$eventName]
            );
        }
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  mixed   $listener
     * @return mixed
     */
    public function makeListener($listener)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener);
        }

        return $listener;
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param  mixed    $listener
     * @return \Closure
     */
    public function createClassListener($listener)
    {
        return function() use ($listener)
        {
            $callable = $this->createClassCallable($listener);

            // We will make a callable of the listener instance and a method that should
            // be called on that instance, then we will pass in the arguments that we
            // received in this method into this listener class instance's methods.
            $data = func_get_args();

            return call_user_func_array($callable, $data);
        };
    }

    /**
     * Create the class based event callable.
     *
     * @param  string  $listener
     * @return callable
     */
    protected function createClassCallable($listener)
    {
        list($className, $method) = $this->parseClassCallable($listener);

        if ($this->handlerShouldBeQueued($className)) {
            return $this->createQueuedHandlerCallable($className, $method);
        }

        $instance = $this->container->make($className);

        return array($instance, $method);
    }

    /**
     * Parse the class listener into class and method.
     *
     * @param  string  $listener
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        // If the listener has an @ sign, we will assume it is being used to delimit
        // the class name from the handle method name. This allows for handlers
        // to run multiple handler methods in a single class for convenience.
        return array_pad(explode('@', $listener, 2), 2, 'handle');
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * @param  string  $className
     * @return bool
     */
    protected function handlerShouldBeQueued($className)
    {
        try {
            return with(new ReflectionClass($className))->implementsInterface('Nova\Queue\ShouldQueueInterface');
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a callable for putting an event handler on the queue.
     *
     * @param  string  $className
     * @param  string  $method
     * @return \Closure
     */
    protected function createQueuedHandlerCallable($className, $method)
    {
        return function () use ($className, $method)
        {
            // Clone the given arguments for queueing.
            $arguments = array_map(function ($a)
            {
                return is_object($a) ? clone $a : $a;

            }, func_get_args());

            if (method_exists($className, 'queue')) {
                $this->callQueueMethodOnHandler($className, $method, $arguments);
            } else {
                $this->resolveQueue()->push('Nova\Events\CallQueuedHandler@call', array(
                    'class'  => $className,
                    'method' => $method,
                    'data'   => serialize($arguments),
                ));
            }
        };
    }

    /**
     * Call the queue method on the handler class.
     *
     * @param  string  $className
     * @param  string  $method
     * @param  array  $arguments
     * @return void
     */
    protected function callQueueMethodOnHandler($className, $method, $arguments)
    {
        $handler = with(new ReflectionClass($className))->newInstanceWithoutConstructor();

        $handler->queue($this->resolveQueue(), 'Nova\Events\CallQueuedHandler@call', array(
            'class'  => $className,
            'method' => $method,
            'data'   => serialize($arguments),
        ));
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        unset($this->listeners[$event], $this->sorted[$event]);
    }

    /**
     * Forget all of the pushed listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (Str::endsWith($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    /**
     * Get the queue implementation from the resolver.
     *
     * @return \Nova\Queue\Contracts\QueueInterface
     */
    protected function resolveQueue()
    {
        return call_user_func($this->queueResolver);
    }

    /**
     * Set the queue resolver implementation.
     *
     * @param  callable  $resolver
     * @return $this
     */
    public function setQueueResolver(callable $resolver)
    {
        $this->queueResolver = $resolver;

        return $this;
    }
}
