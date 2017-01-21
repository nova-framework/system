<?php

namespace Nova\Queue\Connectors;

use Nova\Database\ConnectionResolverInterface;
use Nova\Queue\Queues\DatabaseQueue;
use Nova\Support\Arr;


class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var \Nova\Database\ConnectionResolverInterface
     */
    protected $connections;

    /**
     * Create a new connector instance.
     *
     * @param  \Nova\Database\ConnectionResolverInterface  $connections
     * @return void
     */
    public function __construct(ConnectionResolverInterface $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new DatabaseQueue(
            $this->connections->connection(Arr::get($config, 'connection')),
            $config['table'],
            $config['queue'],
            Arr::get($config, 'expire', 60)
        );
    }
}
