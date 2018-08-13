<?php

namespace Nova\View\Engines;

use Nova\View\Engines\EngineInterface;

use Exception;


class FileEngine implements EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function get($path, array $data = array())
    {
        return file_get_contents($path);
    }
}
