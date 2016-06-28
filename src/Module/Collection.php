<?php

namespace Nova\Module;

use Nova\Foundation\Application;
use Nova\Support\Collection as BaseCollection;


class Collection extends BaseCollection
{
    /**
     * List of all know Modules
     *
     * @var array
     */
    public $items = array();

    /**
     * IoC
     * @var Nova\Foundation\Application
     */
    protected $app;


    /**
     * Initialize a Modules Collection
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Initialize all modules
     * @return void
     */
    public function registerModules()
    {
        // Sort the Modules by their order.
        $this->sort(function($a, $b) {
            if ($a->order == $b->order) return 0;

            return ($a->order < $b->order) ? -1 : 1;
        });

        // Register every know Module.
        foreach ($this->items as $module) {
            $module->register();
        }
    }

}
