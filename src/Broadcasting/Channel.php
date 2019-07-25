<?php

namespace Nova\Broadcasting;


class Channel
{
    /**
     * The channel's name.
     *
     * @var string
     */
    public $name = 'default';


    /**
     * Gets the channel name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Convert the channel instance to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
