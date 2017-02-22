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
        $modules = $this->all();

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

    /**
     * Get modules path.
     *
     * @return string
     */
    public function getPath()
    {
        $path = $this->config->get('modules.path', APPDIR .'Modules');

        return str_replace('/', DS, realpath($path)) .DS;
    }

    /**
     * Get path for the specified module.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getModulePath($slug)
    {
        $module = Str::studly($slug);

        return $this->getPath() .DS .$module .DS;
    }

    /**
     * Get modules namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return rtrim($this->config->get('modules.namespace', 'App\Modules\\'), '/\\');
    }

    public function all()
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

    /**
     * Get all module slugs.
     *
     * @return Collection
     */
    public function slugs()
    {
        $slugs = collect();

        $this->all()->each(function ($item) use ($slugs) {
            $slugs->push($item['slug']);
        });

        return $slugs;
    }

    /**
     * Determines if the given module exists.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function exists($slug)
    {
        if (Str::length($slug) > 3) {
            $slug = Str::snake($slug);
        } else {
            $slug = Str::lower($slug);
        }

        $slugs = $this->slugs()->toArray();

        return in_array($slug, $slugs);
    }

    /**
     * Get modules based on where clause.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Collection
     */
    public function where($key, $value)
    {
        return collect($this->all()->where($key, $value)->first());
    }

    /**
     * Check if specified module is enabled.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function isEnabled($slug)
    {
        $module = $this->where('slug', $slug);

        return ($module['enabled'] === true);
    }
}
