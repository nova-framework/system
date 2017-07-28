<?php

namespace Nova\Support\Facades;

use Nova\Broadcasting\Contracts\FactoryInterface as BroadcastingFactory;


/**
 * @see \Nova\Broadcasting\Contracts\FactoryInterface
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BroadcastingFactory::class;
    }
}
