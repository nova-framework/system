<?php

use Nova\Encryption\Encrypter;
use Nova\Queue\Job;


class CallQueuedClosure
{
    /**
     * The encrypter instance.
     *
     * @var \Nova\Encryption\Encrypter  $crypt
     */
    protected $crypt;


    /**
     * Create a new queued Closure job.
     *
     * @param  \Nova\Encryption\Encrypter  $crypt
     * @return void
     */
    public function __construct(Encrypter $crypt)
    {
        $this->crypt = $crypt;
    }

    /**
     * Fire the Closure based queue job.
     *
     * @param  \Nova\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        $payload = $this->crypt->decrypt(
            $data['closure']
        );

        $closure = unserialize($payload);

        call_user_func($closure, $job);
    }
}
