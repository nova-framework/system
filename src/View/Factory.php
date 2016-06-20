<?php

namespace Nova\View;

use Nova\View\View;


class Factory
{
    /**
     * Create new View Factory instance.
     *
     * @return void
     */
    function __construct()
    {
    }

    /**
     * Create a View instance
     *
     * @param string $path
     * @param array|string $data
     * @param string|null $module
     * @return \Nova\View\View
     */
    public function make($view, $data = array(), $module = null)
    {
        if (is_string($data)) {
            if (! empty($data) && ($module === null)) {
                // The Module name given as second parameter; adjust the information.
                $module = $data;
            }

            $data = array();
        }

        // Get the View file path.
        $path = $this->viewFile($view, $module);

        return new View($view, $path, $data);
    }

    /**
     * Check if the view file exists.
     *
     * @param    string     $view
     * @return    bool
     */
    public function exists($view, $module = null)
    {
        // Get the View file path.
        $path = $this->viewFile($view, $module);

        return file_exists($path);
    }

    /**
     * Get the view file.
     *
     * @param    string     $view
     * @return    string
     */
    protected function viewFile($view, $module = null)
    {
        // Prepare the (relative) file path according with Module parameter presence.
        if ($module !== null) {
            $path = str_replace('/', DS, APPDIR ."Modules/$module/Views/$view.php");
        } else {
            $path = str_replace('/', DS, APPDIR ."Views/$view.php");
        }

        return $path;
    }
}
