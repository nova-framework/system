<?php

namespace Nova\Support\Facades;

use Nova\Broadcasting\FactoryInterface as BroadcastingFactory;


/**
 * @see \Nova\Broadcasting\FactoryInterface
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
