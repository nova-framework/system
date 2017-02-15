<?php

namespace Nova\Support\Facades;


/**
 * @see \Nova\Foundation\Forge
 */
class Forge extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'Nova\Console\Contracts\KernelInterface'; }

}
