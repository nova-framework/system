<?php

namespace Nova\Console\Scheduling;

use Nova\Cache\Repository as Cache;
use Nova\Console\Scheduling\MutexInterface;


class CacheMutex implements MutexInterface
{
    /**
     * The cache repository implementation.
     *
     * @var \Nova\Cache\Repository
     */
    public $cache;


    /**
     * Create a new overlapping strategy.
     *
     * @param  \Nova\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a mutex for the given event.
     *
     * @param  \Nova\Console\Scheduling\Event  $event
     * @return bool
     */
    public function create(Event $event)
    {
        return $this->cache->add(
            $event->mutexName(), true, $event->expiresAt
        );
    }

    /**
     * Determine if a mutex exists for the given event.
     *
     * @param  \Nova\Console\Scheduling\Event  $event
     * @return bool
     */
    public function exists(Event $event)
    {
        return $this->cache->has($event->mutexName());
    }

    /**
     * Clear the mutex for the given event.
     *
     * @param  \Nova\Console\Scheduling\Event  $event
     * @return void
     */
    public function forget(Event $event)
    {
        $this->cache->forget($event->mutexName());
    }
}
