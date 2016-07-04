<?php

namespace Nova\Template;

use Nova\Foundation\Application;
use Nova\View\View;

use Language;


class Factory
{
    /**
     * The Application instance.
     *
     * @var \Foundation\Application
     */
    protected $app;

    /**
     * Create new Template Factory instance.
     *
     * @return void
     */
    function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create a View instance
     *
     * @param string $view
     * @param array|string $data
     * @param string $custom
     * @return \Nova\View\View
     */
    public function make($view, $data = array(), $template = null)
    {
        if (is_string($data)) {
            if (! empty($data) && ($template === null)) {
                // The Module name given as second parameter; adjust the information.
                $template = $data;
            }

            $data = array();
        }

        // Get the View Factory instance.
        $factory = $this->app['view'];

        // Get the View file path.
        $path = $this->viewFile($view, $template);

        $data = $this->parseData($data);

        return new View($factory, $view, $path, $data, true);
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return ($data instanceof Arrayable) ? $data->toArray() : $data;
    }

    /**
     * Check if the view file exists.
     *
     * @param    string     $view
     * @return    bool
     */
    public function exists($view, $template = null)
    {
        // Get the View file path.
        $path = $this->viewFile($view, $template);

        return file_exists($path);
    }

    /**
     * Get the view file.
     *
     * @param    string     $view
     * @return    string
     */
    protected function viewFile($view, $template = null)
    {
        $config = $this->app['config'];

        $language = $this->app['language'];

        // Get the base path.
        $path = $this->app['path'];

        // Calculate the current Template name.
        $template = $template ?: $config['app.template'];

        if ($language->direction() == 'rtl') {
            // The current Language is RTL. Check the path of the RTL Template file.
            $filePath = str_replace('/', DS, "$path/Templates/$template/$view-rtl.php");

            if (is_readable($filePath)) {
                // A valid RTL Template file found; return it.
                return $filePath;
            }
        }

        // Return the path of the current LTR Template file.
        return str_replace('/', DS, "$path/Templates/$template/$view.php");
    }
}
