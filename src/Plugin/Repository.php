<?php

namespace Nova\Plugin;

use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Support\Collection;
use Nova\Support\Str;


class Repository
{
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
    public function __construct(Filesystem $files)
    {
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
        if (isset(static::$plugins)) return static::$plugins;

        return static::$plugins = $this->getPlugins();
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

    protected function getPlugins()
    {
        // Retrieve the Composer's Module information.
        $path = base_path('vendor/nova-plugins.php');

        $plugins = collect();

        try {
            $data = $this->files->getRequire($path);

            if (isset($data['plugins']) && is_array($data['plugins'])) {
                $plugins = collect($data['plugins']);
            }
        }
        catch (FileNotFoundException $e) {
            // Do nothing.
        }

        // Retrieve the local Plugins information.
        $path = $this->getPluginsPath();

        try {
            $paths = collect($this->files->directories($path));

            $paths->each(function ($path) use ($plugins) {
                $plugin = 'Plugins/' .basename($path);

                if (! $plugins->has($plugin)) {
                    $plugins->put($plugin, array('path' => $path .DS, 'location' => 'local', 'theme' => false));
                }
            });
        }
        catch (InvalidArgumentException $e) {
            // Do nothing.
        }

        // Retrieve the local Themes information.
        $path = $this->getThemesPath();

        try {
            $paths = collect($this->files->directories($path));

            $paths->each(function ($path) use ($plugins) {
                $plugin = 'Themes/' .basename($path);

                if (! $plugins->has($plugin)) {
                    $plugins->put($plugin, array('path' => $path .DS, 'location' => 'local', 'theme' => true));
                }
            });
        }
        catch (InvalidArgumentException $e) {
            // Do nothing.
        }

        // Process the retrieved information to generate their records.
        $me = $this;

        $items = $plugins->map(function ($properties, $name) use ($me)
        {
            $basename = $me->getPackageName($name);

            //
            $properties['name'] = $name;

            $properties['slug'] = Str::snake($basename);

            $properties['namespace'] = str_replace('/', '\\', $name);

            $properties['basename'] = $basename;

            return $properties;
        });

        return $items->sortBy('slug');
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
        $plugin = Str::studly($slug);

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
     * Get (local) plugins path.
     *
     * @return string
     */
    public function getThemesPath()
    {
        return base_path('themes');
    }

    /**
     * Get plugins namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return 'Plugins';
    }
}
