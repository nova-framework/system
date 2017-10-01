<?php

namespace Nova\Cache;

use Nova\Cache\Repository;


class RateLimiter
{
    /**
     * The cache store implementation.
     *
     * @var \Nova\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new rate limiter instance.
     *
     * @param  \Nova\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decayMinutes = 1)
    {
        $lockedOut = $this->cache->has($key .':lockout');

        if ($this->attempts($key) > $maxAttempts || $lockedOut) {
            if (! $lockedOut) {
                $this->cache->add($key.':lockout', time() + ($decayMinutes * 60), $decayMinutes);
            }

            return true;
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param  string  $key
     * @param  int  $decayMinutes
     * @return int
     */
    public function hit($key, $decayMinutes = 1)
    {
        $this->cache->add($key, 1, $decayMinutes);

        return (int) $this->cache->increment($key);
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function attempts($key)
    {
        return $this->cache->get($key, 0);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    public function retriesLeft($key, $maxAttempts)
    {
        $attempts = $this->attempts($key);

        return ($attempts === 0) ? $maxAttempts : $maxAttempts - $attempts + 1;
    }

    /**
     * Clear the hits and lockout for the given key.
     *
     * @param  string  $key
     * @return void
     */
    public function clear($key)
    {
        $this->cache->forget($key);

        $this->cache->forget($key .':lockout');
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param  string  $key
     * @return int
     */
    public function availableIn($key)
    {
        return $this->cache->get($key .':lockout') - time();
    }
}
