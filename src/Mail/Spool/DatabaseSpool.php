<?php

namespace Nova\Mail\Spool;

use Nova\Database\Query\Expression;
use Nova\Database\Connection;

use Swift_ConfigurableSpool as BaseSpool;
use Swift_IoException;
use Swift_Mime_Message;
use Swift_Transport;

use Carbon\Carbon;


class DatabaseSpool extends BaseSpool
{
    /**
     * The database connection instance.
     *
     * @var \Nova\Database\Connection
     */
    protected $connection;

    /**
     * The name of the session table.
     *
     * @var string
     */
    protected $table;

    /**
     * The limit of retries while sending the messages.
     *
     * @var int
     */
    protected $retryLimit = 10;


    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;

        $this->table = $table;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start()
    {
        //
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop()
    {
        //
    }

    /**
     * Allow to manage the enqueuing retry limit.
     *
     * Default, is ten and allows over 64^20 different fileNames
     *
     * @param int $limit
     */
    public function setRetryLimit($limit)
    {
        $this->retryLimit = $limit;
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Queues a message.
     *
     * @param \Swift_Mime_Message $message The message to store
     * @return boolean Whether the operation has succeeded
     * @throws \Swift_IoException if the persist fails
     */
    public function queueMessage(Swift_Mime_Message $message)
    {
        $attributes = array(
            'payload'     => serialize($message),
            'attempts'    => 0,
            'reserved'    => 0,
            'reserved_at' => null,
            'created_at'  => $this->getTime(),
        );

        return $this->getQuery()->insertGetId($attributes);
    }

    /**
     * Execute a recovery if for any reason a process is sending for too long.
     *
     * @param int $timeout in second Defaults is for very slow smtp responses
     */
    public function recover($timeout = 900)
    {
        $expired = Carbon::now()->subSeconds($timeout)->getTimestamp();

        $data = array(
            'reserved'    => 0,
            'reserved_at' => null,
            'attempts'    => new Expression('attempts + 1'),
        );

        $this->getQuery()
            ->where('reserved', 1)
            ->where('reserved_at', '<=', $expired)
            ->update($data);
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param \Swift_Transport $transport         A transport instance
     * @param string[]        &$failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null)
    {
        $count = 0;

        // Start the transport only if there are queued messages to send.
        if (! $transport->isStarted() && $this->hasQueuedJobs()) {
            $transport->start();
        }

        $failedRecipients = (array) $failedRecipients;

        $time = $this->getTime();

        while (! is_null($job = $this->pop())) {
            $message = unserialize($job->payload);

            $count += $transport->send($message, $failedRecipients);

            $this->deleteReserved($job->id);

            if ($this->getMessageLimit() && ($count >= $this->getMessageLimit())) {
                break;
            }

            $timeLimit = $this->getTime() - $time;

            if ($this->getTimeLimit() && ($timeLimit >= $this->getTimeLimit())) {
                break;
            }
        }

        return $count;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @return \StdClass|null
     */
    protected function pop()
    {
        if (! is_null($job = $this->getNextAvailableJob())) {
            $this->markJobAsReserved($job->id);

            $this->connection->commit();

            return $job;
        }

        $this->connection->commit();
    }

    /**
     * Get the next available job for the queue.
     *
     * @return \StdClass|null
     */
    protected function getNextAvailableJob()
    {
        $this->connection->beginTransaction();

        $job = $this->getQuery()
            ->lockForUpdate()
            ->where('reserved', 0)
            ->where('attempts', '<=', $this->retryLimit)
            ->orderBy('id', 'asc')
            ->first();

        return $job ? (object) $job : null;
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param  string  $id
     * @return void
     */
    protected function markJobAsReserved($id)
    {
        $this->getQuery()->where('id', $id)->update(array(
            'reserved'    => 1,
            'reserved_at' => $this->getTime(),
        ));
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $id
     * @return void
     */
    public function deleteReserved($id)
    {
        $this->getQuery()->where('id', $id)->delete();
    }

    /**
     * Determine if are queued messages to send.
     *
     * @return void
     */
    protected function hasQueuedJobs()
    {
        return $this->getQuery()
            ->where('reserved', 0)
            ->where('attempts', '<=', $this->retryLimit)
            ->exists();
    }

    /**
     * Get a fresh query builder instance for the table.
     *
     * @return \Nova\Database\Query\Builder
     */
    protected function getQuery()
    {
        return $this->connection->table($this->table);
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
}
