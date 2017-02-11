<?php
/**
 * ConfigLoader - Implements a Configuration Loader.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */
namespace Nova\Config;

use Nova\Config\Config;
use Nova\Filesystem\Filesystem;


class ConfigLoader implements LoaderInterface
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default configuration path.
     *
     * @var string
     */
    protected $defaultPath;


    /**
     * Create a new file configuration loader.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  string  $defaultPath
     * @return void
     */
    public function __construct(Filesystem $files, $defaultPath)
    {
        $this->files = $files;

        $this->defaultPath = $defaultPath;
    }

    /**
     * Load the Configuration Group for the key.
     *
     * @param    string     $group
     * @return     array
     */
    public function load($environment, $group)
    {
        $items = array();

        if (! Config::has($group)) {
            // The Config hasn't loaded this Group; try to load it now.
            $path = $this->getPath();

            $file = $path .DS .ucfirst($group) .'.php';

            if ($this->files->exists($file)) {
                $items = $this->getRequire($file);
            }

            if (! is_array($items)) $items = array();
        } else {
            $items = Config::get($group, array());
        }

        // Merge the Environment options.
        $group = ucfirst($group);

        $environment = ucfirst($environment);

        $file = "{$path}/{$environment}/{$group}.php";

        if ($this->files->exists($file)) {
            $items = $this->mergeEnvironment($items, $file);
        }

        return $items;
    }

    /**
     * Merge the items in the given file into the items.
     *
     * @param  array   $items
     * @param  string  $file
     * @return array
     */
    protected function mergeEnvironment(array $items, $file)
    {
        return array_replace_recursive($items, $this->getRequire($file));
    }

    /**
     * Set a given configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value)
    {
        Config::set($key, $value);
    }

    /**
     * Get the configuration path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->defaultPath;
    }

    /**
     * Get a file's contents by requiring it.
     *
     * @param  string  $path
     * @return mixed
     */
    protected function getRequire($path)
    {
        return $this->files->getRequire($path);
    }
}
