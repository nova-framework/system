<?php

namespace Nova\Foundation\Bus;


trait DispatchableTrait
{
    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Nova\Foundation\Bus\PendingDispatch
     */
    public static function dispatch()
    {
        return new PendingDispatch(new static(...func_get_args()));
    }
}
