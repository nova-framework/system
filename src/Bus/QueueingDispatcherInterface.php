<?php

namespace Nova\Bus;

use Nova\Bus\DispatcherInterface;


interface QueueingDispatcherInterface extends DispatcherInterface
{
    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command);
}
