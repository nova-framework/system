<?php

namespace Nova\Package;

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
    protected static $packages;


    /**
     * Create a new Package Manager instance.
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
     * Get Packages based on where clause.
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
        $package = $this->where('slug', $slug);

        return ($package['enabled'] === true);
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
        $package = $this->where('slug', $slug);

        return ($package['enabled'] === false);
    }

    /**
     * Update cached repository of packages information.
     *
     * @return bool
     */
    public function optimize()
    {
        $path = $this->getCachePath();

        $packages = $this->getPackages();

        $this->writeCache($path, $packages);
    }

    protected function getPackages()
    {
        $dataPath = base_path('vendor/nova-packages.php');

        try {
            $data = $this->files->getRequire($dataPath);

        } catch (FileNotFoundException $e) {
            $data = array();
        }

        // Process the Packages data.
        $path = $this->getPackagesPath();

        $packages = collect();

        foreach (Arr::get($data, 'packages', array()) as $name => $packagePath) {
            $packagePath = realpath($packagePath);

            $location = Str::startsWith($packagePath, $path) ? 'local' : 'vendor';

            $packages->put($name, array('path' => $packagePath .DS, 'location' => $location));
        }

        // Process the retrieved information to generate their records.

        $items = $packages->map(function ($properties, $name)
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

            // Get the Package options from configuration.
            $options = $this->config->get('packages.options.' .$slug, array());

            $properties['enabled'] = Arr::get($options, 'enabled', true);

            $properties['order'] = Arr::get($options, 'order', 9001);

            return $properties;
        });

        return $items->sortBy('basename');
    }

    /**
     * Get the contents of the cache file.
     *
     * The cache file lists all Packages slugs and their enabled or disabled status.
     * This can be used to filter out Packages depending on their status.
     *
     * @return Collection
     */
    public function getCached()
    {
        if (isset(static::$packages)) {
            return static::$packages;
        }

        $cachePath = $this->getCachePath();

        $dataPath = base_path('vendor/nova-packages.php');

        if ($this->isExpired($cachePath, $dataPath)) {
            $packages = $this->getPackages();

            $this->writeCache($cachePath, $packages);
        }

        // The packages cache is valid.
        else {
            $packages = collect(
                $this->files->getRequire($cachePath)
            );
        }

        return static::$packages = $packages;
    }

    /**
     * Write the service cache file to disk.
     *
     * @param  string $path
     * @param  array  $packages
     * @return void
     */
    public function writeCache($path, $packages)
    {
        $data = array();

        foreach ($packages->all() as $key => $package) {
            $properties = ($package instanceof Collection) ? $package->all() : $package;

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

        $this->files->put($path, $content);
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
     * Get (local) Packages path.
     *
     * @return string
     */
    public function getCachePath()
    {
        return storage_path('framework/packages.php');
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
     * Get local path for the specified Package.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getPackagePath($slug)
    {
        $package = (Str::length($slug) > 3) ? Str::studly($slug) : Str::upper($slug);

        return $this->getPath() .DS .$package .DS;
    }

    /**
     * Get (local) Packages path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getPackagesPath();
    }

    /**
     * Get (local) Packages path.
     *
     * @return string
     */
    public function getPackagesPath()
    {
        return base_path('packages');
    }

    /**
     * Get Packages namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return '';
    }
}
