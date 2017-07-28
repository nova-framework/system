<?php

namespace Nova\Queue\Connectors;

use Nova\Queue\Contracts\ConnectorInterface;
use Nova\Queue\Queues\SqsQueue;
use Aws\Sqs\SqsClient;


class SqsConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Queue\Contracts\QueueInterface
     */
    public function connect(array $config)
    {
        $sqs = SqsClient::factory($config);

        return new SqsQueue($sqs, $config['queue']);
    }

}
