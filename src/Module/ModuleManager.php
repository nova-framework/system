<?php

namespace Nova\Module;

use Nova\Foundation\Application;
use Nova\Module\RepositoryInterface;

use LogicException;


class ModuleManager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var RepositoryInterface
     */
    protected $repository;


    /**
     * Create a new ModuleManager instance.
     *
     * @param Application         $app
     * @param RepositoryInterface $repository
     */
    public function __construct(Application $app, RepositoryInterface $repository)
    {
        $this->app = $app;

        $this->repository = $repository;
    }

    /**
     * Register the module service provider file from all modules.
     *
     * @return mixed
     */
    public function register()
    {
        $modules = $this->repository->enabled();

        $modules->each(function ($properties) {
            $this->registerServiceProvider($properties);

            $this->registerAssetsNamespace($properties);

            $this->registerWidgetsNamespace($properties);
        });
    }

    /**
     * Register the Module Service Provider.
     *
     * @param string $properties
     *
     * @return string
     *
     * @throws \Nova\Module\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $namespace = $this->resolveNamespace($properties);

        $serviceProvider = "{$namespace}\\Providers\\ModuleServiceProvider";

        if (class_exists($serviceProvider)) {
            $this->app->register($serviceProvider);
        }
    }

    /**
     * Register the Module Service Provider.
     *
     * @param string $properties
     *
     * @return string
     */
    protected function registerAssetsNamespace($properties)
    {
        if ($properties['location'] === 'vendor') {
            $name = 'assets';
        } else {
            $name = 'Assets';
        }

        $path = $properties['path'] .$name;

        if ($this->app['files']->isDirectory($path)) {
            $this->app['assets']->addNamespace($properties['slug'], $path);
        }
    }

    /**
     * Register the Module Service Provider.
     *
     * @param string $properties
     *
     * @return string
     */
    protected function registerWidgetsNamespace($properties)
    {
        // Determine the Package Widgets path.
        $path = $this->resolveClassPath($properties) .'Widgets';

        $hasWidgets = array_get($properties, 'has-widgets', true);

        if ($hasWidgets && $this->app['files']->isDirectory($path)) {
            $namespace = $this->resolveNamespace($properties);

            $namespace = "{$namespace}\\Widgets";

            $this->app['widgets']->register($namespace);
        }
    }

    /**
     * Resolve the correct module namespace.
     *
     * @param array $properties
     */
    public function resolveNamespace($properties)
    {
        if (isset($properties['namespace'])) return $properties['namespace'];

        throw new LogicException('Namespace not found');
    }

    /**
     * Resolve the correct module files path.
     *
     * @param array $properties
     *
     * @return string
     */
    public function resolveClassPath($properties)
    {
        $path = $properties['path'];

        if ($properties['location'] === 'vendor') {
            $path .= 'src' .DS;
        }

        return $path;
    }

    /**
     * Resolve the correct module files path.
     *
     * @param array  $properties
     * @param string $path
     *
     * @return string
     */
    public function resolveAssetPath($properties, $path)
    {
        $basePath = $properties['path'];

        if ($properties['location'] === 'vendor') {
            $basePath .= 'assets';
        } else {
            $basePath .= 'Assets';
        }

        return $basePath .DS .$path;
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
