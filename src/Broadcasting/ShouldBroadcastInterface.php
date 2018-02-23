<?php

namespace Nova\Broadcasting;


interface ShouldBroadcastInterface
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn();
}
