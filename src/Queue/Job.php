<?php

namespace Nova\Queue;

use Nova\Support\Arr;
use Nova\Support\Str;

use DateTime;


abstract class Job
{
    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;


    /**
     * Fire the job.
     *
     * @return void
     */
    abstract public function handle();

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    abstract public function release($delay = 0);

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    abstract public function attempts();

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    abstract public function getRawBody();

    /**
     * Resolve and fire the job handler method.
     *
     * @param  array  $payload
     * @return void
     */
    protected function resolveAndHandle(array $payload)
    {
        list($class, $method) = Str::parseCallback($payload['job'], 'handle');

        $this->instance = $this->resolve($class);

        call_user_func(array($this->instance, $method), $this, $payload['data']);
    }

    /**
     * Resolve the given job handler.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve($class)
    {
        return $this->container->make($class);
    }

    /**
     * Determine if job should be auto-deleted.
     *
     * @return bool
     */
    public function autoDelete()
    {
        return isset($this->instance->delete);
    }

    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getSeconds($delay)
    {
        if ($delay instanceof DateTime) {
            return max(0, $delay->getTimestamp() - $this->getTime());
        }

        return (int) $delay;
    }

    /**
     * Get the current system time.
     *
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        $payload = json_decode($this->getRawBody(), true);

        return $payload['job'];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * @return string
     */
    public function resolveName()
    {
        $payload = json_decode($this->getRawBody(), true);

        //
        $name = $payload['job'];

        // When the job is a Closure.
        if ($name == 'Nova\Queue\CallQueuedClosure@call') {
            return 'Closure';
        }

        // When the job is a Handler.
        else if ($name == 'Nova\Queue\CallQueuedHandler@call') {
            return Arr::get($payload, 'data.commandName', $name);
        }

        // When the job is an Event.
        else if ($name == 'Nova\Events\CallQueuedHandler@call') {
            $className = Arr::get($payload, 'data.class');

            $method = Arr::get($payload, 'data.method');

            return $className .'@' .$method;
        }

        return $name;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

}
