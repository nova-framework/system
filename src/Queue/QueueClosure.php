<?php

use Nova\Encryption\Encrypter;


class QueueClosure
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
     * @param  \Nova\Queue\Jobs\Job  $job
     * @param  array  $data
     * @return void
     */
    public function fire($job, $data)
    {
        $closure = unserialize($this->crypt->decrypt($data['closure']));

        $closure($job);
    }

}
