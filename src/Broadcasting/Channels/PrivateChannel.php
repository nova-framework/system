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
        parent::__construct('private-' .$name);
    }
}
