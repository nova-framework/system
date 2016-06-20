<?php

namespace Nova\Support\Facades;

use Nova\View\View as Renderer;
use Nova\Support\Facades\Facade;


/**
 * @see \Nova\View\Factory
 */
class View extends Facade
{
    /**
     * Add a key / value pair to the shared View data.
     *
     * Shared View data is accessible to every View created by the application.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public static function share($key, $value)
    {
        Renderer::share($key, $value);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'view'; }

}
