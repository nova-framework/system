<?php

namespace Nova\Queue\Connectors;

use Nova\Queue\SyncQueue;


class SyncConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        return new SyncQueue;
    }

}
