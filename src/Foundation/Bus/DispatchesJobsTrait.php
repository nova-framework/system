<?php

namespace Nova\Foundation\Bus;

use Nova\Bus\DispatcherInterface as Dispatcher;
use Nova\Support\Facades\App;

use ArrayAccess;


trait DispatchesJobsTrait
{

    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function dispatch($job)
    {
        $dispatcher = App::make(Dispatcher::class);

        return $dispatcher->dispatch($job);
    }

    /**
     * Marshal a job and dispatch it to its appropriate handler.
     *
     * @param  mixed  $job
     * @param  array  $array
     * @return mixed
     */
    protected function dispatchFromArray($job, array $array)
    {
        $dispatcher = App::make(Dispatcher::class);

        return $dispatcher->dispatchFromArray($job, $array);
    }

    /**
     * Marshal a job and dispatch it to its appropriate handler.
     *
     * @param  mixed  $job
     * @param  \ArrayAccess  $source
     * @param  array  $extras
     * @return mixed
     */
    protected function dispatchFrom($job, ArrayAccess $source, $extras = array())
    {
        $dispatcher = App::make(Dispatcher::class);

        return $dispatcher->dispatchFrom($job, $source, $extras);
    }
}
