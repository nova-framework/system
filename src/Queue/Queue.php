<?php

namespace Nova\Queue;

use Nova\Container\Container;
use Nova\Encryption\Encrypter;
use Nova\Queue\QueueableEntityInterface;

use SuperClosure\Serializer;

use Closure;
use DateTime;


abstract class Queue
{
    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;


    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Marshal a push queue request and fire the job.
     *
     * @throws \RuntimeException
     */
    public function marshal()
    {
        throw new \RuntimeException("Push queues only supported by Iron.");
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        if ($job instanceof Closure) {
            $payload = $this->createClosurePayload($job, $data);
        } else if (is_object($job)) {
            $payload = $this->createObjectPayload($job, $data);
        } else {
            $payload = array(
                'job'  => $job,
                'data' => $this->prepareQueueableEntities($data)
            );
        }

        return json_encode($payload);
    }

    /**
     * Prepare any queueable entities for storage in the queue.
     *
     * @param  mixed  $data
     * @return mixed
     */
    protected function prepareQueueableEntities($data)
    {
        if ($data instanceof QueueableEntityInterface) {
            return $this->prepareQueueableEntity($data);
        }

        if (is_array($data)) {
            $data = array_map(function ($d)
            {
                if (is_array($d)) {
                    return $this->prepareQueueableEntities($d);
                }

                return $this->prepareQueueableEntity($d);

            }, $data);
        }

        return $data;
    }

    /**
     * Prepare a single queueable entity for storage on the queue.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function prepareQueueableEntity($value)
    {
        if ($value instanceof QueueableEntityInterface) {
            return '::entity::|' .get_class($value) .'|' .$value->getQueueableId();
        }

        return $value;
    }

    /**
     * Create a payload string for the given Closure job.
     *
     * @param  object  $job
     * @param  mixed   $data
     * @return string
     */
    protected function createObjectPayload($job, $data)
    {
        $commandName = get_class($job);

        $command = serialize(clone $job);

        return array(
            'job'  => 'Nova\Queue\CallQueuedHandler@call',

            'data' => compact('commandName', 'command'),
        );
    }

    /**
     * Create a payload string for the given Closure job.
     *
     * @param  \Closure  $job
     * @param  mixed     $data
     * @return string
     */
    protected function createClosurePayload($job, $data)
    {
        $closure = $this->crypt->encrypt(
            with(new Serializer)->serialize($job)
        );

        return array(
            'job'  => 'Nova\Queue\CallQueuedClosure@call',

            'data' => compact('closure')
        );
    }

    /**
     * Set additional meta on a payload string.
     *
     * @param  string  $payload
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function setMeta($payload, $key, $value)
    {
        $payload = json_decode($payload, true);

        return json_encode(array_set($payload, $key, $value));
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
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    public function getTime()
    {
        return time();
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the encrypter instance.
     *
     * @param  \Nova\Encryption\Encrypter  $crypt
     * @return void
     */
    public function setEncrypter(Encrypter $crypt)
    {
        $this->crypt = $crypt;
    }

}
