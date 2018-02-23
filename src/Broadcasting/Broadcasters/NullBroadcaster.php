<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\BroadcasterInterface;
use Nova\Http\Request;


class NullBroadcaster extends BroadcasterInterface
{

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
        //
    }
}
