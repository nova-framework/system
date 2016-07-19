<?php namespace Nova\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Nova\Queue\SqsQueue;

class SqsConnector implements ConnectorInterface {

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Nova\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        $sqs = SqsClient::factory($config);

        return new SqsQueue($sqs, $config['queue']);
    }

}
