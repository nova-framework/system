<?php

namespace Nova\Template;

use Nova\View\Engines\EngineInterface;
use Nova\View\Factory as ViewFactory;
use Nova\View\View;


class Template extends View
{

    public function __construct(ViewFactory $factory, $view, $path, $data = array())
    {
        // Get the View Engine instance.
        $engine = $factory->getEngineFromPath($path);

        //
        parent::__construct($factory, $engine, $view, $path, $data);
    }

}
