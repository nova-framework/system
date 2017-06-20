<?php

namespace Nova\Broadcasting\Broadcasters;

class NullBroadcaster extends Broadcaster
{

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        //
    }
}
