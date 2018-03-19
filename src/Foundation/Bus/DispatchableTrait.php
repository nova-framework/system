<?php

namespace Nova\Foundation\Bus;

use Nova\Foundation\Bus\PendingDispatch;


trait DispatchableTrait
{

    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Nova\Foundation\Bus\PendingDispatch
     */
    public static function dispatch()
    {
        $args = func_get_args();

        return new PendingDispatch(new static(...$args));
    }
}
