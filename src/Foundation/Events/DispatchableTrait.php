<?php

namespace Nova\Foundation\Events;


trait DispatchableTrait
{

    /**
     * Dispatch the event with the given arguments.
     *
     * @return void
     */
    public static function dispatch()
    {
        return event(new static(...func_get_args()));
    }
}
