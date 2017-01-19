<?php

namespace Nova\Layout;

use Nova\View\Factory as ViewFactory;
use Nova\View\View;


class Layout extends View
{

    /**
     * Create a new Layout instance.
     *
     * @return void
     */
    public function __construct(ViewFactory $factory, $name, $path, array $data = array())
    {
        $engine = $factory->getEngineFromPath($path);

        //
        parent::__construct($factory, $engine, $name, $path, $data);
    }

}
