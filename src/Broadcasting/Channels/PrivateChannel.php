<?php

namespace Nova\Broadcasting\Channels;

use Nova\Broadcasting\Channel as BaseChannel;


class PrivateChannel extends BaseChannel
{
    /**
     * Create a new channel instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = sprintf('private-%s', $name);
    }
}
