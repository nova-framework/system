<?php

namespace Nova\Plugins;

use Nova\Config\Repository as Config;
use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Support\Collection;
use Nova\Support\Arr;
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
     * @var \Nova\Support\Collection|null
     */
    protected static $plugins;


    /**
     * Create a new Plugin Manager instance.
     *
     * @param Application $app
     */
    public function __construct(Config $config, Filesystem $files)
    {
        $this->config = $config;

        $this->files = $files;
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

    public function all()
    {
        return $this->getCached()->sortBy('order');
    }

    /**
     * Get plugins based on where clause.
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
        $plugin = $this->where('slug', $slug);

        return ($plugin['enabled'] === true);
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
        $plugin = $this->where('slug', $slug);

        return ($plugin['enabled'] === false);
    }

    protected function getPlugins()
    {
        $dataPath = base_path('vendor/nova-plugins.php');

        $cachePath = storage_path('plugins.php');

        if ($this->isExpired($cachePath, $dataPath)) {
            // Retrieve the Composer's Plugins information.

            try {
                $data = $this->files->getRequire($dataPath);

            } catch (FileNotFoundException $e) {
                $data = array();
            }

            // Process the plugins data.
            $path = $this->getPluginsPath();

            $plugins = collect();

            foreach (Arr::get($data, 'plugins', array()) as $name => $pluginPath) {
                $pluginPath = realpath($pluginPath);

                $location = Str::startsWith($pluginPath, $path) ? 'local' : 'vendor';

                $plugins->put($name, array('path' => $pluginPath .DS, 'location' => $location));
            }

            // Process the retrieved information to generate their records.

            $items = $plugins->map(function ($properties, $name)
            {
                $basename = $this->getPackageName($name);

                if (Str::length($basename) > 3) {
                    $slug =  Str::snake($basename);
                } else {
                    $slug = Str::lower($basename);
                }

                $properties['name'] = $name;
                $properties['slug'] = $slug;

                $properties['namespace'] = str_replace('/', '\\', $name);

                $properties['basename'] = $basename;

                // Get the Plugin options from configuration.
                $options = $this->config->get('plugins.options.' .$slug, array());

                $properties['enabled'] = Arr::get($options, 'enabled', true);

                $properties['order'] = Arr::get($options, 'order', 9001);

                return $properties;
            });

            $this->writeCache($cachePath, $items);
        } else {
            $items = collect(
                $this->files->getRequire($cachePath)
            );
        }

        return $items->sortBy('basename');
    }

    /**
     * Get the contents of the cache file.
     *
     * The cache file lists all plugins slugs and their enabled or disabled status.
     * This can be used to filter out plugins depending on their status.
     *
     * @return Collection
     */
    public function getCached()
    {
        if (isset(static::$plugins)) {
            return static::$plugins;
        }

        return static::$plugins = $this->getPlugins();
    }

    /**
     * Write the service cache file to disk.
     *
     * @param  array  $plugins
     * @param  string $cachePath
     * @return void
     */
    public function writeCache($cachePath, $plugins)
    {
        $data = array();

        foreach ($plugins->all() as $key => $plugin) {
            $properties = ($plugin instanceof Collection) ? $plugin->all() : $plugin;

            // Normalize to *nix paths.
            $properties['path'] = str_replace('\\', '/', $properties['path']);

            //
            ksort($properties);

            $data[$key] = $properties;
        }

        //
        $data = var_export($data, true);

        $content = <<<PHP
<?php

return $data;

PHP;

        $this->files->put($cachePath, $content);
    }

    /**
    * Determine if the cache file is expired.
    *
    * @param  string  $path
    * @return bool
    */
    public function isExpired($cachePath, $path)
    {
        if (! $this->files->exists($cachePath)) {
            return true;
        }

        $lastModified = $this->files->lastModified($path);

        if ($lastModified >= $this->files->lastModified($cachePath)) {
            return true;
        }

        return false;
    }

    /**
     * Get the name for a Package.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @return string
     */
    protected function getPackageName($package)
    {
        if (strpos($package, '/') === false) {
            return $package;
        }

        list($vendor, $namespace) = explode('/', $package);

        return $namespace;
    }

    /**
     * Get local path for the specified plugin.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getPluginPath($slug)
    {
        $plugin = (Str::length($slug) > 3) ? Str::studly($slug) : Str::upper($slug);

        return $this->getPath() .DS .$plugin .DS;
    }

    /**
     * Get (local) plugins path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getPluginsPath();
    }

    /**
     * Get (local) plugins path.
     *
     * @return string
     */
    public function getPluginsPath()
    {
        return base_path('plugins');
    }

    /**
     * Get plugins namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return '';
    }
}
