<?php

namespace Nova\Package;

use Nova\Foundation\Application;
use Nova\Package\Repository;
use Nova\Support\Str;


class PackageManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var \Nova\Package\Repository
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
     * @throws \Nova\Package\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $basename = $properties['basename'];

        $namespace = $this->resolveNamespace($properties);

        if (isset($properties['type']) && ! empty($type = $properties['type'])) {
            $name = Str::studly($type);
        } else {
            $name = 'Package';
        }

        if (class_exists($provider = "{$namespace}\\Providers\\{$name}ServiceProvider")) {
            //
        }

        // If not exists the default Service Provider, try the alternate one.
        else if (! class_exists($provider = "{$namespace}\\{$basename}ServiceProvider")) {
            return;
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
        return $properties['path'] .'src' .DS;
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
