<?php

namespace Nova\Packages;

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
        return $this->getPackagesCached()->sortBy('order');
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
        return $this->getPackagesCached()->sortBy($key);
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
        return $this->getPackagesCached()->sortByDesc($key);
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
        $slug = (Str::length($slug) <= 3) ? Str::lower($slug) : Str::snake($slug);

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
     * Get local path for the specified Package.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getPackagePath($slug)
    {
        $package = (Str::length($slug) <= 3) ? Str::upper($slug) : Str::studly($slug);

        return $this->getPackagesPath() .DS .$package .DS;
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
    public function getPackagesNamespace()
    {
        return '';
    }

    /**
     * Get path for the specified Module.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getModulePath($slug)
    {
        $module = (Str::length($slug) <= 3) ? Str::upper($slug) : Str::studly($slug);

        return $this->getModulesPath() .DS .$module .DS;
    }

    /**
     * Get modules path.
     *
     * @return string
     */
    public function getModulesPath()
    {
        return $this->config->get('packages.modules.path', BASEPATH .'modules');
    }

    /**
     * Get modules namespace.
     *
     * @return string
     */
    public function getModulesNamespace()
    {
        $namespace = $this->config->get('packages.modules.namespace', 'Modules\\');

        return rtrim($namespace, '/\\');
    }

    /**
     * Get path for the specified Theme.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getThemePath($slug)
    {
        $theme = (Str::length($slug) <= 3) ? Str::upper($slug) : Str::studly($slug);

        return $this->getThemesPath() .DS .$theme .DS;
    }

    /**
     * Get modules path.
     *
     * @return string
     */
    public function getThemesPath()
    {
        return $this->config->get('packages.themes.path', BASEPATH .'themes');
    }

    /**
     * Get modules namespace.
     *
     * @return string
     */
    public function getThemesNamespace()
    {
        $namespace = $this->config->get('packages.themes.namespace', 'Themes\\');

        return rtrim($namespace, '/\\');
    }

    /**
     * Update cached repository of packages information.
     *
     * @return bool
     */
    public function optimize()
    {
        $path = $this->getCachePath();

        $this->writeCache($path, $this->getPackages());
    }

    protected function getPackages()
    {
        $packagesPath = base_path('vendor/nova-packages.php');

        try {
            $data = $this->files->getRequire($packagesPath);

        } catch (FileNotFoundException $e) {
            $data = array();
        }

        $items = Arr::get($data, 'packages', array());

        // Process the Packages data.
        $path = $this->getPackagesPath();

        $packages = collect();

        foreach ($items as $name => $packagePath) {
            $location = Str::startsWith($packagePath, $path) ? 'local' : 'vendor';

            $packages->put($name, array(
                'path' => Str::finish($packagePath, DS),

                //
                'location' => $location,
                'type'     => 'package',
            ));
        }

        //
        // Process for the local Modules.

        $path = $this->getModulesPath();

        if ($this->files->isDirectory($path)) {
            try {
                $paths = collect(
                    $this->files->directories($path)
                );
            }
            catch (InvalidArgumentException $e) {
                $paths = collect();
            }

            $namespace = $this->getModulesNamespace();

            $vendor = class_basename($namespace);

            $paths->each(function ($path) use ($packages, $vendor)
            {
                $name = $vendor .'/' .basename($path);

                $packages->put($name, array(
                    'path' => Str::finish($path, DS),

                    //
                    'location' => 'local',
                    'type'     => 'module',
                ));
            });
        }

        //
        // Process for the local Themes.

        $path = $this->getThemesPath();

        if ($this->files->isDirectory($path)) {
            try {
                $paths = collect(
                    $this->files->directories($path)
                );
            }
            catch (InvalidArgumentException $e) {
                $paths = collect();
            }

            $namespace = $this->getThemesNamespace();

            $vendor = class_basename($namespace);

            $paths->each(function ($path) use ($packages, $vendor)
            {
                $name = $vendor .'/' .basename($path);

                $packages->put($name, array(
                    'path' => Str::finish($path, DS),

                    //
                    'location' => 'local',
                    'type'     => 'theme',
                ));
            });
        }

        //
        // Process the retrieved information to generate their records.

        $items = $packages->map(function ($properties, $name)
        {
            $basename = $this->getPackageName($name);

            $slug = (Str::length($basename) <= 3) ? Str::lower($basename) : Str::snake($basename);

            //
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
    protected function getPackagesCached()
    {
        if (isset(static::$packages)) {
            return static::$packages;
        }

        $configPath = app_path('Config/Packages.php');

        $packagesPath = base_path('vendor/nova-packages.php');

        //
        $path = $this->getCachePath();

        if (! $this->isCacheExpired($path, $packagesPath) && ! $this->isCacheExpired($path, $configPath)) {
            $data = (array) $this->files->getRequire($path);

            return static::$packages = collect($data);
        }

        $this->writeCache($path, $packages = $this->getPackages());

        return static::$packages = $packages;
    }

    /**
     * Write the service cache file to disk.
     *
     * @param  string $path
     * @param  array  $packages
     * @return void
     */
    protected function writeCache($path, $packages)
    {
        $data = array();

        foreach ($packages->all() as $key => $package) {
            $properties = ($package instanceof Collection) ? $package->all() : $package;

            // Normalize to *nix paths.
            $properties['path'] = str_replace('\\', '/', $properties['path']);

            //
            ksort($properties);

            $data[] = $properties;
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
    * @param  string  $cachePath
    * @param  string  $path
    * @return bool
    */
    protected function isCacheExpired($cachePath, $path)
    {
        if (! $this->files->exists($cachePath)) {
            return true;
        }

        $lastModified = $this->files->lastModified($path);

        if ($lastModified < $this->files->lastModified($cachePath)) {
            return false;
        }

        return true;
    }

    /**
     * Get packages cache path.
     *
     * @return string
     */
    public function getCachePath()
    {
        return $this->config->get('packages.cache', STORAGE_PATH .'framework' .DS .'packages.php');
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

        list ($vendor, $namespace) = explode('/', $package);

        return $namespace;
    }
}
