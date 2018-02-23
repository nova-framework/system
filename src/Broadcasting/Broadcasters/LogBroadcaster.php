<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\BroadcasterInterface;
use Nova\Http\Request;

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
        $message = 'Broadcasting [' .$event .'] on channels [' .implode(', ', $channels) .'] with payload:' .PHP_EOL
                        .json_encode($payload, JSON_PRETTY_PRINT);

        $this->logger->info($message);
    }
}
