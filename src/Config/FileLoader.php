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


class FileLoader implements LoaderInterface
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
     * A cache of whether groups exists.
     *
     * @var array
     */
    protected $exists = array();


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
     * @param string $environment
     * @param string $group
     * @return array
     */
    public function load($environment, $group)
    {
        $items = array();

        $group = ucfirst($group);

        // Load the options from the group's file.
        $path = $this->getPath();

        $file = $path .DS .$group .'.php';

        if ($this->files->exists($file)) {
            $items = $this->getRequire($file);
        }

        // Merge the Environment options.
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
     * Determine if the given group exists.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return bool
     */
    public function exists($group)
    {
        $group = ucfirst($group);

        if (isset($this->exists[$group])) {
            return $this->exists[$group];
        }

        $path = $this->getPath();

        $file = "{$path}/{$group}.php";

        $exists = $this->files->exists($file);

        return $this->exists[$group] = $exists;
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

    /**
     * Get the Filesystem instance.
     *
     * @return \Nova\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
