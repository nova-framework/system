<?php

namespace Nova\Module\Repositories;

use Nova\Helpers\Inflector;
use Nova\Module\Repositories\Repository;
use Nova\Support\Collection;


class LocalRepository extends Repository
{

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
        return collect($this->all()->where($key, $value)->first());
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

        //
        $cache = $this->getCache();

        $module = $this->where('slug', $slug);

        if (isset($module[$key])) {
            unset($module[$key]);
        }

        $module[$key] = $value;

        $module = collect(array($module['basename'] => $module));

        $merged = $cache->merge($module);

        //
        $this->writeCache($merged);
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
        $module = $this->where('slug', $slug);

        return ($module['enabled'] === true);
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
        $module = $this->where('slug', $slug);

        return ($module['enabled'] === false);
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

    /*
    |--------------------------------------------------------------------------
    | Optimization Methods
    |--------------------------------------------------------------------------
    |
    */

    /**
     * Update cached repository of module information.
     *
     * @return bool
     */
    public function optimize()
    {
        $cachePath = $this->getCachePath();
        $cache     = $this->getCache();
        $items     = $this->getAllModules();

        //
        $modules = collect();

        $items->each(function ($item) use ($modules, $cache) {
            $module = $item['basename'];

            $collection = collect($item);

            $temp = $collection->merge(collect($cache->get($module)));

            $manifest = $temp->merge(collect($this->getManifest($item)));

            $modules->put($module, $manifest);
        });

        $modules->each(function ($module) {
            $module->put('id', crc32($module->get('slug')));

            if (! $module->has('enabled')) {
                $module->put('enabled', $this->config->get('modules.enabled', true));
            }

            if (! $module->has('order')) {
                $module->put('order', 9001);
            }

            return $module;
        });

        //
        $this->writeCache($modules);
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
            $this->createCache();

            $this->optimize();
        }

        //
        $data = $this->files->getRequire($cachePath);

        return collect($data);
    }

    /**
     * Create an empty instance of the cache file.
     *
     * @return Collection
     */
    private function createCache()
    {
        $cachePath = $this->getCachePath();

        $collection = collect();

        $this->writeCache($collection);

        return $collection;
    }

    /**
     * Write the service cache file to disk.
     *
     * @param  array  $modules
     * @return void
     */
    public function writeCache($modules)
    {
        $cachePath = $this->getCachePath();

        //
        $data = array();

        foreach ($modules->all() as $key => $module) {
            $data[$key] = ($module instanceof Collection) ? $module->all() : $module;
        }

        //
        $content = "<?php\n\nreturn " .var_export($data, true) .";\n";

        $this->files->put($cachePath, $content);
    }

    /**
     * Get the path to the cache file.
     *
     * @return string
     */
    protected function getCachePath()
    {
        return STORAGE_PATH .'framework' .DS .'modules.php';
    }
}
