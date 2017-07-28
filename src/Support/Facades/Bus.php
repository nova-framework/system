<?php

namespace Nova\Support\Facades;

use Nova\Bus\Contracts\DispatcherInterface as BusDispatcher;


/**
 * @see \Nova\Bus\Dispatcher
 */
class Bus extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BusDispatcher::class;
    }
}
