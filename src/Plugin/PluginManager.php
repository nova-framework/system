<?php

namespace Nova\Plugin;

use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Foundation\Application;
use Nova\Support\Collection;
use Nova\Support\Str;


class PluginManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

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
    public function __construct(Application $app, Filesystem $files)
    {
        $this->app = $app;

        $this->files = $files;
    }

    /**
     * Register the plugin service provider file from all plugins.
     *
     * @return mixed
     */
    public function register()
    {
        $plugins = $this->getPlugins();

        $plugins->each(function($properties)
        {
            $this->registerServiceProvider($properties);
        });
    }

    /**
     * Register the Plugin Service Provider.
     *
     * @param array $properties
     *
     * @return void
     *
     * @throws \Nova\Plugin\FileMissingException
     */
    protected function registerServiceProvider($properties)
    {
        $namespace = $this->resolveNamespace($properties);

        // Calculate the name of Service Provider, including the namespace.
        $serviceProvider = "{$namespace}\\Providers\\PluginServiceProvider";

        if (class_exists($serviceProvider)) {
            $this->app->register($serviceProvider);
        }
    }

    /**
     * Resolve the correct Plugin namespace.
     *
     * @param array $properties
     */
    public function resolveNamespace($properties)
    {
        if (isset($properties['namespace'])) return $properties['namespace'];

        return Str::studly($properties['slug']);
    }

    public function all()
    {
        if (isset(static::$plugins)) return static::$plugins;

        return static::$plugins = $this->getPlugins();
    }

    public function getPath()
    {
        return base_path('plugins');
    }

    public function getPlugins()
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

        // Retrieve the local Modules information.
        $path = $this->getPath();

        try {
            $paths = collect($this->files->directories($path));

            $paths->each(function ($path) use ($plugins) {
                $plugin = 'Plugins/' .basename($path);

                if (! $plugins->has($plugin)) {
                    $plugins->put($plugin, array('path' => $path .DS, 'location' => 'local'));
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
}
