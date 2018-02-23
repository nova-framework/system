<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Container\Container;
use Nova\Http\Request;

use Psr\Log\LoggerInterface;


class LogBroadcaster extends Broadcaster
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
    public function __construct(Container $container, LoggerInterface $logger)
    {
        parent::__construct($container);

        //
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function validAuthenticationResponse(Request $request, $result)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $channels = implode(', ', $this->formatChannels($channels));

        $message = 'Broadcasting [' .$event .'] on channels [' .$channels .'] with payload:' .PHP_EOL .json_encode($payload, JSON_PRETTY_PRINT);

        $this->logger->info($message);
    }
}
