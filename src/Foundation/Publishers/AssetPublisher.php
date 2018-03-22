<?php

namespace Nova\Foundation\Publishers;

use Nova\Filesystem\Filesystem;


class AssetPublisher
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path where assets should be published.
     *
     * @var string
     */
    protected $publishPath;

    /**
     * The path where packages are located.
     *
     * @var string
     */
    protected $packagePath;


    /**
     * Create a new asset publisher instance.
     *
     * @param  \Nova\\Filesystem\Filesystem  $files
     * @param  string  $publishPath
     * @return void
     */
    public function __construct(Filesystem $files, $publishPath)
    {
        $this->files = $files;

        $this->publishPath = $publishPath;
    }

    /**
     * Copy all assets from a given path to the publish path.
     *
     * @param  string  $name
     * @param  string  $source
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function publish($name, $source)
    {
        $package = str_replace('_', '-', $name);

        $destination = $this->publishPath .str_replace('/', DS, "/packages/{$package}");

        if (! $this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        }

        $success = $this->files->copyDirectory($source, $destination);

        if (! $success) {
            throw new \RuntimeException("Unable to publish assets.");
        }

        return $success;
    }

    /**
     * Publish a given package's assets to the publish path.
     *
     * @param  string  $package
     * @param  string  $packagePath
     * @return bool
     */
    public function publishPackage($package, $packagePath = null)
    {
        $source = $packagePath ?: $this->packagePath;

        return $this->publish($package, $source);
    }

    /**
     * Set the default package path.
     *
     * @param  string  $packagePath
     * @return void
     */
    public function setPackagePath($packagePath)
    {
        $this->packagePath = $packagePath;
    }

}
