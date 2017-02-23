<?php

namespace Nova\Module;

use Nova\Config\Repository as Config;
use Nova\Support\Collection;
use Nova\Support\Str;


class Repository
{
    /**
     * @var \Nova\Config\Repository
     */
    protected $config;

    /**
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var string Path to the defined Modules directory
     */
    protected $path;

    /**
     * @var \Nova\Support\Collection|null
     */
    protected static $modules;


    /**
     * Constructor method.
     *
     * @param \Nova\Config\Repository     $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function all()
    {
        if (isset(static::$modules)) return static::$modules;

        //
        $modules = $this->config->get('modules.modules', array());

        $modules = array_map(function($slug, $properties)
        {
            $namespace = isset($properties['namespace']) ? $properties['namespace'] : Str::studly($slug);

            $name = isset($properties['name']) ? $properties['name'] : $namespace;

            return array_merge(array(
                'slug'      => $slug,
                'name'      => $name,
                'basename'  => $name,
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

    /**
     * Get modules path.
     *
     * @return string
     */
    public function getPath()
    {
        $path = $this->config->get('modules.path', APPDIR .'Modules');

        return str_replace('/', DS, realpath($path));
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
}
