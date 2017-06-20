<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Contracts\BroadcasterInterface;


class NullBroadcaster implements BroadcasterInterface
{

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        //
    }
}
