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
    public function load($group)
    {
        $items = array();

        if (! Config::has($group)) {
            // The Config hasn't loaded this Group; try to load it now.
            $path = $this->getPath();

            $filePath = $path .DS .ucfirst($group) .'.php';

            if ($this->files->exists($filePath)) {
                $items = $this->getRequire($filePath);
            }

            if (is_array($items)) return $items;
        }

        return Config::get($group, array());
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
