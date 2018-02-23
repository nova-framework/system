<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\BroadcasterInterface;

use Psr\Log\LoggerInterface;


class LogBroadcaster implements BroadcasterInterface
{
    /**
     * The logger implementation.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $channels = implode(', ', $channels);

        $payload = json_encode($payload, JSON_PRETTY_PRINT);

        $message = 'Broadcasting [' .$event .'] on channels [' .$channels .'] with payload:' .PHP_EOL .$payload;

        $this->logger->info($message);
    }
}
