<?php

namespace Nova\Queue;

use Nova\Cache\Repository as CacheRepository;
use Nova\Events\Dispatcher;
use Nova\Queue\Failed\FailedJobProviderInterface;
use Nova\Queue\Job;
use Nova\Support\Str;

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
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public $shouldQuit = false;


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
        $this->failer  = $failer;
        $this->events  = $events;
        $this->manager = $manager;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $memory
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return array
     */
    public function daemon($connection, $queue, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
    {
        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if (! $this->daemonShouldRun($connection, $queue)) {
                $this->sleep($sleep);
            } else {
                $this->runNextJob($connection, $queue, $delay, $sleep, $maxTries);
            }

            if ($this->daemonShouldQuit()) {
                $this->kill();
            }

            // Check if the daemon should be stopped.
            else if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart)) {
                $this->stop();
            }
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return bool
     */
    protected function daemonShouldRun($connection, $queue)
    {
        if ($this->manager->isDownForMaintenance()) {
            return false;
        }

        $result = $this->events->until('nova.queue.looping', array($connection, $queue));

        return ($result !== false);
    }

    /**
     * Returns true if the daemon should quit.
     *
     * @return bool
     */
    protected function daemonShouldQuit()
    {
        return $this->shouldQuit;
    }

    /**
     * Listen to the given queue.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return array
     */
    public function runNextJob($connection, $queue = null, $delay = 0, $sleep = 3, $maxTries = 0)
    {
        $job = $this->getNextJob(
            $this->manager->connection($connection), $queue
        );

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out. If there is no job on the queue
        // we will "sleep" the worker for the specified number of seconds.

        if (! is_null($job)) {
            return $this->runJob($job, $connection, $maxTries, $delay);
        }

        $this->sleep($sleep);

        return array('job' => null, 'failed' => false);
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param  \Nova\Queue\Queue  $connection
     * @param  string  $queue
     * @return \Nova\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        try {
            if (is_null($queue)) {
                return $connection->pop();
            }

            foreach (explode(',', $queue) as $queue) {
                if (! is_null($job = $connection->pop($queue))) {
                    return $job;
                }
            }
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
        catch (Throwable $e) {
            $this->handleException(new FatalThrowableError($e));
        }
    }

    /**
     * Process the given job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $connection
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function runJob($job, $connection, $maxTries, $delay)
    {
        try {
            return $this->process($connection, $job, $maxTries, $delay);
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
        catch (Throwable $e) {
            $this->handleException(new FatalThrowableError($e));
        }
    }

    /**
     * Handle an exception that occurred while handling a job.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function handleException($e)
    {
        if (isset($this->exceptions)) {
            $this->exceptions->report($e);
        }

        if ($this->causedByLostConnection($e)) {
            $this->shouldQuit = true;
        }
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Exception
     * @return bool
     */
    protected function causedByLostConnection(Exception $e)
    {
        return Str::contains($e->getMessage(), array(
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ));
    }

    /**
     * Process a given job from the queue.
     *
     * @param  string  $connection
     * @param  \Nova\Queue\Job  $job
     * @param  int  $maxTries
     * @param  int  $delay
     * @return void
     *
     * @throws \Exception
     */
    public function process($connection, Job $job, $maxTries = 0, $delay = 0)
    {
        if (($maxTries > 0) && ($job->attempts() > $maxTries)) {
            return $this->logFailedJob($connection, $job);
        }

        // First we will raise the before job event and fire off the job. Once it is done
        // we will see if it will be auto-deleted after processing and if so we will go
        // ahead and run the delete method on the job. Otherwise we will just keep moving.

        try {
            $this->raiseBeforeJobEvent($connection, $job);

            $job->handle();

            if ($job->autoDelete()) {
                $job->delete();
            }

            $this->raiseAfterJobEvent($connection, $job);

            return array('job' => $job, 'failed' => false);
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
     * @param  string  $connection
     * @param  \Nova\Queue\Job  $job
     * @return array
     */
    protected function logFailedJob($connection, Job $job)
    {
        if (isset($this->failer)) {
            $this->failer->log(
                $connection, $job->getQueue(), $job->getRawBody()
            );

            $job->delete();

            $this->raiseFailedJobEvent($connection, $job);
        }

        return array('job' => $job, 'failed' => true);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  string  $connection
     * @param  \Nova\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connection, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.processing', array($connection, $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connection
     * @param  \Nova\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connection, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.processed', array($connection, $job));
        }
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  string  $connection
     * @param  \Nova\Queue\Job  $job
     * @return void
     */
    protected function raiseFailedJobEvent($connection, Job $job)
    {
        if (isset($this->events)) {
            $this->events->fire('nova.queue.failed', array($connection, $job));
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
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        $this->events->fire('nova.queue.stopping');

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
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
