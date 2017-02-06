<?php

namespace Nova\Module;

use Nova\Helpers\Inflector;
use Nova\Foundation\Application;
use Nova\Module\RepositoryInterface;


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

            $this->registerWidgetsNamespace($properties);

            $this->autoloadFiles($properties);
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

        $serviceProvider = "{$namespace}\\Providers\\{$namespace}ServiceProvider";

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
     *
     * @throws \Nova\Module\FileMissingException
     */
    protected function registerWidgetsNamespace($properties)
    {
        $widgets = $this->app['widgets'];

        //
        $namespace = $this->resolveNamespace($properties);

        $namespace = $this->repository->getNamespace() ."\\{$namespace}\\Widgets";

        $widgets->register($namespace);
    }

    /**
     * Autoload custom module files.
     *
     * @param array $properties
     */
    protected function autoloadFiles($properties)
    {
        if (isset($properties['autoload']) && is_array($properties['autoload'])) {
            $autoload = $properties['autoload'];

            if (empty($autoload)) return;
        } else {
            $autoload = array('config', 'events', 'filters', 'routes', 'bootstrap');
        }

        $basePath = $this->resolveClassPath($properties);

        foreach ($autoload as $name) {
            $path = $basePath .ucfirst($name) .'.php';

            if (is_readable($path)) require $path;
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
