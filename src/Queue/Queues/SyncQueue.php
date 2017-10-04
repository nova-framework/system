<?php

namespace Nova\Queue\Queues;

use Nova\Queue\Jobs\SyncJob;
use Nova\Queue\Queue;
use Nova\Queue\Contracts\QueueInterface;


class SyncQueue extends Queue implements QueueInterface
{

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->resolveJob($job, json_encode($data))->handle();

        return 0;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Nova\Queue\Jobs\Job|null
     */
    public function pop($queue = null) {}

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $job
     * @param  string  $data
     * @return \Nova\Queue\Jobs\SyncJob
     */
    protected function resolveJob($job, $data)
    {
        return new SyncJob($this->container, $job, $data);
    }

}
