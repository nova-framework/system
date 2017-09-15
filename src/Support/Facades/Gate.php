<?php

namespace Nova\Support\Facades;

/**
 * @see \Nova\Auth\Access\GateInterface
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Nova\Auth\Contracts\Access\GateInterface';
    }
}
