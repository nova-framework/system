<?php

namespace Nova\Foundation;

use Nova\Filesystem\Filesystem;


class ConfigPublisher
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The destination of the config files.
     *
     * @var string
     */
    protected $publishPath;

    /**
     * The path to the application's packages.
     *
     * @var string
     */
    protected $packagePath;


    /**
     * Create a new configuration publisher instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  string  $publishPath
     * @return void
     */
    public function __construct(Filesystem $files, $publishPath)
    {
        $this->files = $files;

        $this->publishPath = $publishPath;
    }

    /**
     * Publish configuration files from a given path.
     *
     * @param  string  $package
     * @param  string  $source
     * @return bool
     */
    public function publish($package, $source)
    {
        $destination = $this->getDestinationPath($package);

        $this->makeDestination($destination);

        return $this->files->copyDirectory($source, $destination);
    }

    /**
     * Publish the configuration files for a package.
     *
     * @param  string  $package
     * @param  string  $packagePath
     * @return bool
     */
    public function publishPackage($package, $packagePath = null)
    {
        // First we will figure out the source of the package's configuration location
        // which we do by convention. Once we have that we will move the files over
        // to the "main" configuration directory for this particular application.
        $path = $packagePath ?: $this->packagePath;

        $source = $this->getSource($package, $path);

        return $this->publish($package, $source);
    }

    /**
     * Get the source configuration directory to publish.
     *
     * @param  string  $package
     * @param  string  $packagePath
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getSource($package, $packagePath)
    {
        $source = $packagePath .str_replace('/', DS, "/{$package}/src/Config");

        if (! $this->files->isDirectory($source)) {
            throw new \InvalidArgumentException("Configuration not found.");
        }

        return $source;
    }

    /**
     * Create the destination directory if it doesn't exist.
     *
     * @param  string  $destination
     * @return void
     */
    protected function makeDestination($destination)
    {
        if ( ! $this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        }
    }

    /**
     * Determine if a given package has already been published.
     *
     * @param  string  $package
     * @return bool
     */
    public function alreadyPublished($package)
    {
        $path = $this->getDestinationPath($package);

        return $this->files->isDirectory($path);
    }

    /**
     * Get the target destination path for the configuration files.
     *
     * @param  string  $package
     * @return string
     */
    public function getDestinationPath($package)
    {
        return $this->publishPath .str_replace('/', DS, "/Packages/{$package}");
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
