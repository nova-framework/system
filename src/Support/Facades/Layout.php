<?php

namespace Nova\Support\Facades;

use Nova\Support\Facades\Facade;

/**
 * @see \Nova\Layout\Factory
 * @see \Nova\Layout\Layout
 */
class Layout extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'layout'; }

}
