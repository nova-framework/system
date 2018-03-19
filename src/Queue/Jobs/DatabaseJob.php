<?php

namespace Nova\Queue\Jobs;

use Nova\Container\Container;
use Nova\Queue\Queues\DatabaseQueue;
use Nova\Queue\Job;


class DatabaseJob extends Job
{
    /**
     * The database queue instance.
     *
     * @var \Nova\Queue\DatabaseQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var \StdClass
     */
    protected $job;


    /**
     * Create a new job instance.
     *
     * @param  \Nova\Container\Container  $container
     * @param  \Nova\Queue\DatabaseQueue  $database
     * @param  \StdClass  $job
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, DatabaseQueue $database, $job, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;

        $this->job->attempts = $this->job->attempts + 1;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function handle()
    {
        $payload = json_decode($this->getRawBody(), true);

        $this->resolveAndHandle($payload);
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->payload;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, $this->job->id);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->delete();

        $this->database->release($this->queue, $this->job, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->id;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Nova\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the underlying queue driver instance.
     *
     * @return \Nova\Queue\DatabaseQueue
     */
    public function getDatabaseQueue()
    {
        return $this->database;
    }

    /**
     * Get the underlying database job.
     *
     * @return \StdClass
     */
    public function getDatabaseJob()
    {
        return $this->job;
    }
}
