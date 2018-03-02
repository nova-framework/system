<?php

namespace Nova\Queue;

use Nova\Cache\Repository as CacheRepository;
use Nova\Events\Dispatcher;
use Nova\Queue\Failed\FailedJobProviderInterface;
use Nova\Queue\Job;

use Symfony\Component\Debug\Exception\FatalThrowableError;

use Exception;
use Throwable;


class Worker
{

    /**
     * The queue manager instance.
     *
     * @var \Nova\Queue\QueueManager
     */
    protected $manager;

    /**
     * The failed job provider implementation.
     *
     * @var \Nova\Queue\Failed\FailedJobProviderInterface
     */
    protected $failer;

    /**
     * The event dispatcher instance.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The cache repository implementation.
     *
     * @var \Nova\Cache\Repository
     */
    protected $cache;

    /**
     * The exception handler instance.
     *
     * @var \Nova\Foundation\Exceptions\Handler
     */
    protected $exceptions;

    /**
     * Create a new queue worker.
     *
     * @param  \Nova\Queue\QueueManager  $manager
     * @param  \Nova\Queue\Contracts\FailedJobProviderInterface  $failer
     * @param  \Nova\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(QueueManager $manager, FailedJobProviderInterface $failer = null, Dispatcher $events = null)
    {
        $this->failer    = $failer;
        $this->events    = $events;
        $this->manager    = $manager;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $memory
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return array
     */
    public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
    {
        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if (! $this->daemonShouldRun()) {
                $this->sleep($sleep);
            } else {
                $this->runNextJobForDaemon(
                    $connectionName, $queue, $delay, $sleep, $maxTries
                );
            }

            if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart)) {
                $this->stop();
            }
        }
    }

    /**
     * Run the next job for the daemon worker.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $sleep
     * @param  int  $maxTries
     * @return void
     */
    protected function runNextJobForDaemon($connectionName, $queue, $delay, $sleep, $maxTries)
    {
        try {
            $this->pop($connectionName, $queue, $delay, $sleep, $maxTries);
        }
        catch (Exception $e) {
            if (isset($this->exceptions)) {
                $this->exceptions->report($e);
            }
        }
        catch (Throwable $e) {
            if (isset($this->exceptions)) {
                $this->exceptions->report(new FatalThrowableError($e));
            }
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun()
    {
        if ($this->manager->isDownForMaintenance()) {
            return false;
        }

        $result = $this->events->until('nova.queue.looping');

        return ($result !== false);
    }

    /**
     * Listen to the given queue.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return array
     */
    public function pop($connectionName, $queue = null, $delay = 0, $sleep = 3, $maxTries = 0)
    {
        $connection = $this->manager->connection($connectionName);

        $job = $this->getNextJob($connection, $queue);

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out. If there is no job on the queue
        // we will "sleep" the worker for the specified number of seconds.

        if (is_null($job)) {
            $this->sleep($sleep);

            return array('job' => null, 'failed' => false);
        }

        return $this->process(
            $this->manager->getName($connectionName), $job, $maxTries, $delay
        );
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param  \Nova\Queue\Queue  $connection
     * @param  string  $queue
     * @return \Nova\Queue\Jobs\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        if (is_null($queue)) {
            return $connection->pop();
        }

        foreach (explode(',', $queue) as $queue) {
            if (! is_null($job = $connection->pop($queue))) {
                return $job;
            }
        }
    }

    /**
     * Process a given job from the queue.
     *
     * @param  string  $connectionName
     * @param  \Nova\Queue\Jobs\Job  $job
     * @param  int  $maxTries
     * @param  int  $delay
     * @return void
     *
     * @throws \Exception
     */
    public function process($connectionName, Job $job, $maxTries = 0, $delay = 0)
    {
        if (($maxTries > 0) && ($job->attempts() > $maxTries)) {
            return $this->logFailedJob($connectionName, $job);
        }

        // First we will raise the before job event and fire off the job. Once it is done
        // we will see if it will be auto-deleted after processing and if so we will go
        // ahead and run the delete method on the job. Otherwise we will just keep moving.

        try {
            $this->raiseBeforeJobEvent($connectionName, $job);

            $job->handle();

            if ($job->autoDelete()) {
                $job->delete();
            }

            $this->raiseAfterJobEvent($connectionName, $job);
        }

        // If we catch an exception, we will attempt to release the job back onto
        // the queue so it is not lost. This will let is be retried at a later
        // time by another listener (or the same one). We will do that here.

        catch (Exception $e) {
            if (! $job->isDeleted()) {
                $job->release($delay);
            }

            throw $e;
        }
        catch (Throwable $e) {
            if (! $job->isDeleted()) {
                $job->release($delay);
            }

            throw $e;
        }
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connectionName
     * @param  \Nova\Queue\Jobs\Job  $job
     * @return array
     */
    protected function logFailedJob($connectionName, Job $job)
    {
        if (! isset($this->failer)) {
            return array('job' => $job, 'failed' => true);
        }

        $this->failer->log($connectionName, $job->getQueue(), $job->getRawBody());

        $job->delete();

        $this->raiseFailedJobEvent($connectionName, $job);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  string  $connectionName
     * @param  \Nova\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connectionName, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.processing', array($connectionName, $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connectionName
     * @param  \Nova\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connectionName, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.processed', array($connectionName, $job));
        }
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  string  $connectionName
     * @param  \Nova\Queue\Jobs\Job  $job
     * @return void
     */
    protected function raiseFailedJobEvent($connectionName, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.failed', array($connectionName, $job));
        }
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        $memoryUsage = memory_get_usage() / 1024 / 1024;

        return ($memoryUsage >= $memoryLimit);
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @return void
     */
    public function stop()
    {
        $this->events->fire('nova.queue.stopping');

        die;
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        if (isset($this->cache)) {
            return $this->cache->get('nova:queue:restart');
        }
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return ($this->getTimestampOfLastQueueRestart() != $lastRestart);
    }

    /**
     * Set the exception handler to use in Daemon mode.
     *
     * @param  \Nova\Exception\Handler  $handler
     * @return void
     */
    public function setDaemonExceptionHandler($handler)
    {
        $this->exceptions = $handler;
    }

    /**
     * Set the cache repository implementation.
     *
     * @param  \Nova\Cache\Repository  $cache
     * @return void
     */
    public function setCache(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the queue manager instance.
     *
     * @return \Nova\Queue\QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Nova\Queue\QueueManager  $manager
     * @return void
     */
    public function setManager(QueueManager $manager)
    {
        $this->manager = $manager;
    }

}
