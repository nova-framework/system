<?php

namespace Nova\Module\Repositories;

use Nova\Config\Repository as Config;
use Nova\Helpers\Inflector;
use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Module\RepositoryInterface;
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
     * @var \Nova\Support\Collection|null;
     */
    protected $installed;


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
        $path = base_path('vendor/nova-modules.php');

        try {
            $data = $this->files->getRequire($path);

            if (isset($data['modules']) && is_array($data['modules'])) {
                $collection = collect($data['modules']);
            } else {
                throw new InvalidArgumentException('Invalid modules data');
            }

            $modules = $collection->map(function ($item, $key) {
                $path = str_replace(BASEPATH, '', $item);

                $local = ! starts_with($path, 'vendor');

                return array('basename' => $key, 'path' => $item, 'local' => $local);
            });

            return $modules;
        }
        catch (FileNotFoundException $e) {
            return collect(array());
        }
        catch (InvalidArgumentException $e) {
            return collect(array());
        }
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
        if ($module['local'] === true) {
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

        $data = json_decode($contents, true);

        //
        $name = array_get($module, 'basename');

        if ($data['type'] !== 'nova-module') {
            throw new LogicException("The Composer package [$name] is not a Nova module");
        }

        $slug = Inflector::tableize(str_replace('/', '_', $name));

        $version = $this->getPackageVersion($data['name']);

        $properties = array(
            'name'        => $name,
            'version'     => $version,
            'description' => array_get($data, 'description'),
            'homepage'    => array_get($data, 'homepage'),
            'authors'     => array_get($data, 'authors'),
            'license'     => array_get($data, 'license'),
            'namespace'   => str_replace('/', '\\', $name),
            'slug'        => array_get($data, 'extra.slug', $slug),
            'order'       => array_get($data, 'extra.order', 9001),
        );

        return collect($properties);
    }

    protected function getPackageVersion($name)
    {
        if (! isset($this->installed)) {
            $path = base_path('vendor/composer/installed.json');

            $contents = $this->files->get($path);

            $this->installed = collect(json_decode($contents, true));
        }

        $data = $this->installed->where('name', $name)->first();

        $version = array_get($data, 'version_normalized', 'v1.0.0');

        //
        if ($version == '9999999-dev') return __d('nova', 'development');

        return ltrim($version, 'v');
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
        $module = Inflector::classify($slug);

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
        return rtrim($this->config->get('modules.namespace'), '/\\');
    }
}
