<?php

namespace Nova\Modules\Repositories;

use Nova\Helpers\Inflector;


class LocalRepository extends Repository
{
    /**
     * Update cached repository of module information.
     *
     * @return bool
     */
    public function optimize()
    {
        $cachePath = $this->getCachePath();
        $cache     = $this->getCache();
        $basenames = $this->getAllBasenames();

        $modules = collect();

        $basenames->each(function ($module) use ($modules, $cache) {
            $temp = collect($cache->get($module));

            $manifest = collect($this->getManifest($module));

            $modules->put($module, $temp->merge($manifest));
        });

        $modules->each(function ($module) {
            if (! $module->has('enabled')) {
                $module->put('enabled', $this->config->get('modules.enabled', true));
            }

            if (! $module->has('order')) {
                $module->put('order', 9001);
            }

            return $module;
        });

        //
        $content = $modules->toJson();

        return $this->files->put($cachePath, $content);
    }

    /**
     * Get all modules.
     *
     * @return Collection
     */
    public function all()
    {
        return $this->getCache()->sortBy('order');
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
     * Get modules based on where clause.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Collection
     */
    public function where($key, $value)
    {
        return $this->all()->where($key, $value);
    }

    /**
     * Sort modules by given key in ascending order.
     *
     * @param string $key
     *
     * @return Collection
     */
    public function sortBy($key)
    {
        $collection = $this->all();

        return $collection->sortBy($key);
    }

    /**
     * Sort modules by given key in ascending order.
     *
     * @param string $key
     *
     * @return Collection
     */
    public function sortByDesc($key)
    {
        $collection = $this->all();

        return $collection->sortByDesc($key);
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
        $slug = Inflector::tableize($slug);

        $slugs = $this->slugs()->toArray();

        return in_array($slug, $slugs);
    }

    /**
     * Returns count of all modules.
     *
     * @return int
     */
    public function count()
    {
        return $this->all()->count();
    }

    /**
     * Get a module property value.
     *
     * @param string $property
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($property, $default = null)
    {
        list($slug, $key) = explode('::', $property);

        $module = $this->where('slug', $slug);

        return $module->get($key, $default);
    }

    /**
     * Set the given module property value.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return bool
     */
    public function set($property, $value)
    {
        list($slug, $key) = explode('::', $property);

        $cachePath = $this->getCachePath();

        $cache = $this->getCache();

        $module = $this->where('slug', $slug);

        $moduleKey = $module->keys()->first();

        $values = $module->first();

        if (isset($values[$key])) {
            unset($values[$key]);
        }

        $values[$key] = $value;

        $module = collect(array($moduleKey => $values));

        $merged = $cache->merge($module);

        $content = $merged->toJson();

        return $this->files->put($cachePath, $content);
    }

    /**
     * Get all enabled modules.
     *
     * @return Collection
     */
    public function enabled()
    {
        return $this->all()->where('enabled', true);
    }

    /**
     * Get all disabled modules.
     *
     * @return Collection
     */
    public function disabled()
    {
        return $this->all()->where('enabled', false);
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
        $module = $this->where('slug', $slug)
            ->first();

        return $module['enabled'] === true;
    }

    /**
     * Check if specified module is disabled.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function isDisabled($slug)
    {
        $module = $this->where('slug', $slug)
            ->first();

        return $module['enabled'] === false;
    }

    /**
     * Enables the specified module.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function enable($slug)
    {
        return $this->set($slug .'::enabled', true);
    }

    /**
     * Disables the specified module.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function disable($slug)
    {
        return $this->set($slug .'::enabled', false);
    }

    /**
     * Get the contents of the cache file.
     *
     * The cache file lists all module slugs and their
     * enabled or disabled status. This can be used to
     * filter out modules depending on their status.
     *
     * @return Collection
     */
    public function getCache()
    {
        $cachePath = $this->getCachePath();

        if (! $this->files->exists($cachePath)) {
            $content = json_encode(array());

            $this->files->put($cachePath, $content);

            $this->optimize();

            return collect(json_decode($content, true));
        }

        return collect(json_decode($this->files->get($cachePath), true));
    }

    /**
     * Get the path to the cache file.
     *
     * @return string
     */
    protected function getCachePath()
    {
        return storage_path('modules.json');
    }
}
