<?php

namespace Nova\Module;

use Nova\Config\Repository as Config;
use Nova\Foundation\Application;
use Nova\Support\Collection;
use Nova\Support\Str;


class ModuleManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var \Nova\Config\Repository
     */
    protected $config;

    /**
     * @var \Nova\Support\Collection|null
     */
    protected static $modules;


    /**
     * Create a new ModuleRepository instance.
     *
     * @param Application         $app
     * @param RepositoryInterface $repository
     */
    public function __construct(Application $app, Config $config)
    {
        $this->app = $app;

        $this->config = $config;
    }

    /**
     * Register the module service provider file from all modules.
     *
     * @return mixed
     */
    public function register()
    {
        $modules = $this->getModules();

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
     * @throws \Nova\Module\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $namespace = $this->resolveNamespace($properties);

        // Calculate the name of Service Provider, including the namespace.
        $serviceProvider = $this->getNamespace() ."{$namespace}\\Providers\\ModuleServiceProvider";

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
        if (isset($properties['namespace'])) return $properties['namespace'];

        return Str::studly($properties['slug']);
    }

    public function getPath()
    {
        $path = $this->config->get('modules.path', APPDIR .'Modules');

        return str_replace('/', DS, realpath($path)) .DS;
    }

    public function getNamespace()
    {
        return $this->config->get('modules.namespace', 'App\Modules\\');
    }

    public function getModules()
    {
        if (isset(static::$modules)) return static::$modules;

        //
        $modules = $this->config->get('modules.modules', array());

        $modules = array_map(function($slug, $properties)
        {
            $namespace = isset($properties['namespace']) ? $properties['namespace'] : Str::studly($slug);

            return array_merge(array(
                'slug'      => $slug,
                'name'      => isset($properties['name']) ? $properties['name'] : $namespace,
                'namespace' => $namespace,
                'enabled'   => isset($properties['enabled']) ? $properties['enabled'] : true,
                'order'     => isset($properties['order'])   ? $properties['order']   : 9001,
            ), $properties);

        }, array_keys($modules), $modules);

        return static::$modules = Collection::make($modules)->sortBy('order');
    }

}
