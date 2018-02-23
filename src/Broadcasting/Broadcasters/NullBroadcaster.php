<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\BroadcasterInterface;


class NullBroadcaster extends BroadcasterInterface
{

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        //
    }
}
