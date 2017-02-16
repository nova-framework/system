<?php

namespace Nova\Queue;

use Nova\Queue\Job;


trait InteractsWithQueue
{
    /**
     * The underlying queue job instance.
     *
     * @var \Nova\Queue\Job
     */
    protected $job;

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->job) {
            return $this->job->delete();
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        if ($this->job) {
            return $this->job->release($delay);
        }
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Set the base queue job instance.
     *
     * @param  \Nova\Queue\Job  $job
     * @return $this
     */
    public function setJob(Job $job)
    {
        $this->job = $job;

        return $this;
    }
}
