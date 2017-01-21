<?php

namespace Nova\Queue\Connectors;


interface ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Queue\QueueInterface
     */
    public function connect(array $config);

}
