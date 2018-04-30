<?php

namespace Nova\Packages;

use Nova\Foundation\Application;
use Nova\Packages\Repository;
use Nova\Support\Str;


class PackageManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var \Nova\Packages\Repository
     */
    protected $repository;


    /**
     * Create a new Package Manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app = $app;

        $this->repository = $repository;
    }

    /**
     * Register the Package service provider file from all Packages.
     *
     * @return mixed
     */
    public function register()
    {
        $packages = $this->repository->enabled();

        $packages->each(function($properties)
        {
            $this->registerServiceProvider($properties);
        });
    }

    /**
     * Register the Package Service Provider.
     *
     * @param array $properties
     *
     * @return void
     *
     * @throws \Nova\Packages\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $namespace = $this->resolveNamespace($properties);

        $name = Str::studly(
            isset($properties['type']) ? $properties['type'] : 'package'
        );

        // The main service provider from a package should be named like:
        // AcmeCorp\Pages\Providers\PackageServiceProvider

        $provider = "{$namespace}\\Providers\\{$name}ServiceProvider";

        if (! class_exists($provider)) {
            // We will try to find the alternate service provider, named like:
            // AcmeCorp\Pages\PageServiceProvider

            $name = Str::singular(
                $properties['basename']
            );

            if (! class_exists($provider = "{$namespace}\\{$name}ServiceProvider")) {
                return;
            }
        }

        $this->app->register($provider);
    }

    /**
     * Resolve the correct Package namespace.
     *
     * @param array $properties
     */
    public function resolveNamespace($properties)
    {
        if (isset($properties['namespace'])) {
            return $properties['namespace'];
        }

        return Str::studly($properties['slug']);
    }

    /**
     * Resolve the correct Package files path.
     *
     * @param array $properties
     *
     * @return string
     */
    public function resolveClassPath($properties)
    {
        $path = $properties['path'];

        if ($properties['type'] == 'package') {
            return $path .'src' .DS;
        }

        return $path;
    }

    /**
     * Dynamically pass methods to the repository.
     *
     * @param string $method
     * @param mixed  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->repository, $method), $arguments);
    }
}
