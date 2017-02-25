<?php

namespace Nova\Module;

use Nova\Config\Repository as Config;
use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Module\Contracts\RepositoryInterface;
use Nova\Support\Str;

use InvalidArgumentException;
use LogicException;


abstract class Repository implements RepositoryInterface
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
     * Constructor method.
     *
     * @param \Nova\Config\Repository     $config
     * @param \Nova\Filesystem\Filesystem $files
     */
    public function __construct(Config $config, Filesystem $files)
    {
        $this->config = $config;

        $this->files = $files;
    }

    /**
     * Get all defined modules.
     *
     * @return array
     */
    protected function getAllModules()
    {
        // Retrieve the Composer's Module information.
        $path = base_path('vendor/nova-modules.php');

        $modules = collect();

        try {
            $data = $this->files->getRequire($path);

            if (isset($data['modules']) && is_array($data['modules'])) {
                $modules = collect($data['modules']);
            }
        }
        catch (FileNotFoundException $e) {
            // Do nothing.
        }

        // Retrieve the local Modules information.
        $namespace = $this->getNamespace();

        $path = $this->getPath();

        try {
            $paths = collect($this->files->directories($path));

            $paths->each(function ($path) use ($modules, $namespace) {
                $module = $namespace .'/' .basename($path);

                if (! $modules->has($module)) {
                    // Determine the local Package version.
                    $filePath = $path .DS .'module.json';

                    if (is_readable($filePath)) {
                        $properties = json_decode(file_get_contents($filePath), true);

                        $version = $properties['version'];
                    } else {
                        $version = '0.0.0';
                    }

                    $modules->put($module, array(
                        'path'     => $path .DS,
                        'version'  => $version,
                        'location' => 'local',
                    ));
                }
            });
        }
        catch (InvalidArgumentException $e) {
            // Do nothing.
        }

        // Process the retrieved information to generate their records.
        $me = $this;

        $items = $modules->map(function ($properties, $name) use ($me)
        {
            $basename = $me->getPackageName($name);

            //
            $properties['name'] = $name;

            $properties['basename'] = $basename;

            return $properties;
        });

        return $items->sortBy('basename');
    }

    /**
     * Get a module's manifest contents.
     *
     * @param string $slug
     *
     * @return Collection|null
     */
    public function getManifest(array $module)
    {
        if ($module['location'] === 'local') {
            // A local Module; retrieve the Manifest from its module.json
            $path = $module['path'] .'module.json';

            $contents = $this->files->get($path);

            return collect(json_decode($contents, true));
        }

        return $this->getManifestFromPackage($module);
    }

    protected function getManifestFromPackage(array $module)
    {
        $path = $module['path'] .'composer.json';

        $contents = $this->files->get($path);

        $composer = json_decode($contents, true);

        //
        $package = array_get($composer, 'name');

        $name = array_get($module, 'name');

        if ($composer['type'] !== 'nova-module') {
            throw new LogicException("The Composer Package [$package] is not a Nova module");
        }

        $slug = Str::snake(str_replace('/', '_', $name));

        $version = ltrim($module['version'], 'v');

        $properties = array(
            'name'        => $name,
            'version'     => $version,
            'description' => array_get($composer, 'description'),
            'homepage'    => array_get($composer, 'homepage'),
            'authors'     => array_get($composer, 'authors'),
            'license'     => array_get($composer, 'license'),
            'namespace'   => str_replace('/', '\\', $name),
            'slug'        => array_get($composer, 'extra.slug', $slug),
            'order'       => array_get($composer, 'extra.order', 9001),
        );

        return collect($properties);
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
     * Get modules path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path ?: $this->config->get('modules.path');
    }

    /**
     * Set modules path in "RunTime" mode.
     *
     * @param string $path
     *
     * @return object $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
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
     * Get path of module manifest file.
     *
     * @param string $module
     *
     * @return string
     */
    protected function getManifestPath(array $module)
    {
        return  $module['path'] .'module.json';
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
}
