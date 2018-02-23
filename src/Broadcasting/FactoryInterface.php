<?php

namespace Nova\Broadcasting;


interface FactoryInterface
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string  $name
     * @return void
     */
    public function connection($name = null);
}
