<?php

namespace Nova\Queue\Connectors;

use Nova\Queue\NullQueue;


class NullConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new NullQueue;
    }
}
