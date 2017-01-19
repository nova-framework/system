<?php

namespace Nova\Layout;

use Nova\Foundation\Application;
use Nova\Language\LanguageManager;
use Nova\Support\Contracts\ArrayableInterface as Arrayable;
use Nova\Support\Facades\Config;
use Nova\Layout\Layout;
use Nova\View\Factory as ViewFactory;
use Nova\View\ViewFinderInterface;
use Nova\View\View;


class Factory
{
    /**
     * The Application instance.
     *
     * @var \Nova\View\Factory
     */
    protected $views;

    /**
     * The view finder implementation.
     *
     * @var \Nova\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * The Application instance.
     *
     * @var \Nova\Language\LanguageManager
     */
    protected $language;

    /**
     * Array of registered view name aliases.
     *
     * @var array
     */
    protected $aliases = array();


    /**
     * Create new Layout Factory instance.
     *
     * @param $factory The View Factory instance.
     * @return void
     */
    function __construct(ViewFactory $views, ViewFinderInterface $finder, LanguageManager $language)
    {
        $this->views = $views;

        $this->finder = $finder;

        $this->language = $language;
    }

    /**
     * Create a View instance
     *
     * @param string $view
     * @param array $data
     * @param string|null $template
     * @return \Nova\View\View
     */
    public function make($view, array $data = array(), $template = null)
    {
        if (isset($this->aliases[$view])) $view = $this->aliases[$view];

        // Calculate the current Template name.
        $template = $template ?: Config::get('app.template');

        // Get the View file path.
        $path = $this->find($view, $template);

        // Normalize the Layout name.
        $name = 'Layout/' .$template .'::' .str_replace('/', '.', $view);

        //
        $this->views->callCreator($layout = new Layout($this->views, $name, $path, $this->parseData($data)));

        return $layout;
    }

    /**
     * Add an alias for a view.
     *
     * @param  string  $view
     * @param  string  $alias
     * @return void
     */
    public function alias($view, $alias)
    {
        $this->aliases[$alias] = $view;
    }

    /**
     * Check if the view file exists.
     *
     * @param    string     $view
     * @return    bool
     */
    public function exists($view, $template = null)
    {
        try {
            $this->find($view, $template);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
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
     * Find the View file.
     *
     * @param    string     $view
     * @param    string     $template
     * @return    string
     */
    protected function find($view, $template)
    {
        // Calculate the search path.
        $path = sprintf('Templates/%s/%s', $template, $view);

        // Make the path absolute and adjust the directory separator.
        $path = APPPATH .str_replace('/', DS, $path);

        // Find the View file depending on the Language direction.
        $language = $this->getCurrentLanguage();

        if ($language->direction() == 'rtl') {
            // Search for the View file used on the RTL languages.
            $filePath = $this->finder->find($path .'-rtl');
        } else {
            $filePath = null;
        }

        if (is_null($filePath)) {
            $filePath = $this->finder->find($path);
        }

        if (! is_null($filePath)) return $filePath;

        throw new \InvalidArgumentException("Unable to load the view '" .$view ."' on template '" .$template ."'.", 1);
    }

    /**
     * Return the current Language instance.
     *
     * @return \Language\Language
     */
    protected function getCurrentLanguage()
    {
        return $this->language->instance();
    }
}
