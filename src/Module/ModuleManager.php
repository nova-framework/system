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

        $file = $this->repository->getPath() .DS .$namespace .DS .'Providers' .DS .$namespace .'ServiceProvider.php';

        $serviceProvider = $this->repository->getNamespace() ."\\{$namespace}\\Providers\\{$namespace}ServiceProvider";

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
        if (! isset($properties['autoload'])) {
            $files = array('Config.php', 'Events.php', 'Filters.php', 'Routes.php', 'Bootstrap.php');
        } else {
            $files = $properties['autoload'];
        }

        $basePath = $this->resolveFilesPath($properties);

        foreach ($files as $file) {
            $path = $basePath .$file;

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

        return Inflector::classify($properties['slug']);
    }

    /**
     * Resolve the correct module files path.
     *
     * @param array $properties
     */
    public function resolveFilesPath($properties)
    {
        $path = $properties['path'];

        if ($properties['local'] === false) {
            $path .= 'src' .DS;
        }

        return $path;
    }

    /**
     * Resolve the correct module files path.
     *
     * @param array $properties
     */
    public function resolveAssetsPath($properties)
    {
        $path = $properties['path'];

        if ($properties['local'] === false) {
            return $path .'assets' .DS;
        }

        return $path .'Assets' .DS;
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
