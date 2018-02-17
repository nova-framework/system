<?php

namespace Nova\Modules;

use Nova\Config\Repository as Config;
use Nova\Filesystem\Filesystem;
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
    public function __construct(Config $config, Filesystem $files)
    {
        $this->config = $config;

        $this->files = $files;
    }

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

        $this->all()->each(function ($item) use ($slugs)
        {
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
        if (Str::length($slug) > 3) {
            $slug = Str::snake($slug);
        } else {
            $slug = Str::lower($slug);
        }

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
     * Get modules path.
     *
     * @return string
     */
    public function getPath()
    {
        $path = $this->config->get('modules.path', BASEPATH .'modules');

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
        $module = (Str::length($slug) > 3) ? Str::studly($slug) : Str::upper($slug);

        return $this->getPath() .DS .$module .DS;
    }

    /**
     * Get modules namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return rtrim($this->config->get('modules.namespace', 'Modules\\'), '/\\');
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
        $modules = $this->getAllModules();

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
        if ($this->isCacheExpired()) {
            $modules = $this->getAllModules();

            $this->writeCache($modules);

            return $modules;
        }

        $cachePath = $this->getCachePath();

        $data = $this->files->getRequire($cachePath);

        return collect($data);
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
             $properties = ($module instanceof Collection) ? $module->all() : $module;

             ksort($properties);

             $data[$key] = $properties;
        }

        ksort($data);

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
        return $this->config->get('modules.cache', STORAGE_PATH .'modules.php');
    }

    /*
     * Determine if the cahe is expired.
     *
     * @return bool
     */
    public function isCacheExpired()
    {
        $cachePath = $this->getCachePath();

        if (! $this->files->exists($cachePath)) {
            return true;
        }

        // Determine the path to the associated configuration file.
        $path = APPPATH .'Config' .DS .'Modules.php';

        $lastModified = $this->files->lastModified($path);

        if ($lastModified >= $this->files->lastModified($cachePath)) {
            return true;
        }

        return false;
    }

    /**
     * Get all defined modules.
     *
     * @return array
     */
    protected function getAllModules()
    {
        $path = $this->getPath();

        // Get the Modules from configuration.
        $config = $this->config->get('modules.modules', array());

        $modules = collect($config);

        // Retrieve all local Modules information.
        $classPath = str_replace('\\', '/', $this->getNamespace());

        $vendor = basename($classPath);

        try {
            $paths = collect($this->files->directories($path));
        }
        catch (InvalidArgumentException $e) {
            // Do nothing.
            $paths = collect();
        }

        $paths->each(function ($path) use (&$modules, $vendor)
        {
            $basename = basename($path);

            $slug = (Str::length($basename) > 3) ? Str::snake($basename) : Str::lower($basename);

            if (! $modules->has($slug)) {
                $name = $vendor .'/' .basename($path);

                $modules->put($slug, array(
                    'name'     => $name,
                    'basename' => $basename,
                    'enabled'  =>true,
                    'order'    => 9001
                ));
            }
        });

        // Process the collected Modules information.
        $items = $modules->map(function ($properties, $slug)
        {
            $name = isset($properties['name']) ? $properties['name'] : 'Modules/' .Str::studly($slug);

            $basename = isset($properties['basename']) ? $properties['basename'] : Str::studly($slug);

            return array_merge(array(
                'slug'      => $slug,
                'name'      => $name,
                'basename'  => $basename,
                'namespace' => $basename,
                'enabled'   => isset($properties['enabled']) ? $properties['enabled'] : true,
                'order'     => isset($properties['order'])   ? $properties['order']   : 9001,
            ), $properties);

        });

        return $items->sortBy('basename');
    }
}
