<?php

namespace Nova\Modules;

use Nova\Config\Repository as Config;
use Nova\Foundation\Application;
use Nova\Modules\Repository;
use Nova\Support\Collection;
use Nova\Support\Str;


class ModuleManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var Nova\Modules\Repository
     */
    protected $repository;

    /**
     * @var \Nova\Config\Repository
     */
    protected $config;


    /**
     * Create a new ModuleManager instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
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
        $modules = $this->repository->all();

        $modules->each(function($properties)
        {
            $enabled = array_get($properties,'enabled', true);

            if ($enabled) {
                $this->registerServiceProvider($properties);
            }
        });
    }

    /**
     * Register the Module Service Provider.
     *
     * @param array $properties
     *
     * @return void
     *
     * @throws \Nova\Modules\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $namespace = $this->repository->getNamespace();

        // Resolve the Module namespace.
        $module = $this->resolveNamespace($properties);

        // Calculate the name of Service Provider, including the namespace.
        $serviceProvider = "{$namespace}\\{$module}\\Providers\\ModuleServiceProvider";

        if (class_exists($serviceProvider)) {
            $this->app->register($serviceProvider);
        }
    }

    /**
     * Resolve the correct Module namespace.
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
