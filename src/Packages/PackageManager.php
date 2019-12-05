<?php

namespace Nova\Packages;

use Nova\Foundation\Application;
use Nova\Packages\Exception\ProviderMissingException;
use Nova\Packages\Repository;
use Nova\Support\Arr;
use Nova\Support\Str;

use Exception;
use LogicException;


class PackageManager
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var \Nova\Packages\Repository
     */
    protected $repository;


    /**
     * Create a new Package Manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app = $app;

        $this->repository = $repository;
    }

    /**
     * Register the Package service provider file from all Packages.
     *
     * @return mixed
     */
    public function register()
    {
        $packages = $this->repository->enabled();

        $packages->each(function ($properties)
        {
            try {
                $provider = $this->resolveServiceProvider($properties);

                $this->app->register($provider);
            }
            catch (Exception $e) {
                // Do nothing.
            }
        });
    }

    /**
     * Resolve the class name of a Package Service Provider.
     *
     * @param array $properties
     *
     * @return string
     * @throws \LogicException|\Nova\Packages\Exception\ProviderMissingException
     */
    protected function resolveServiceProvider(array $properties)
    {
        if (empty($name = Arr::get($properties, 'name'))) {
            throw new LogicException('Invalid Package properties');
        }

        $namespace = Arr::get($properties, 'namespace', str_replace('/', '\\', $name));

        // The default service provider from a package should be named like:
        // AcmeCorp\Pages\Providers\PackageServiceProvider

        $type = Arr::get($properties, 'type', 'package');

        $provider = sprintf('%s\\Providers\\%sServiceProvider', $namespace, Str::studly($type));

        if (class_exists($provider)) {
            return $provider;
        }

        // The alternate service provider from a package should be named like:
        // AcmeCorp\Pages\PageServiceProvider

        $basename = Arr::get($properties, 'basename', basename($name));

        $provider = sprintf('%s\%sServiceProvider', $namespace, Str::singular($basename));

        if (class_exists($provider)) {
            return $provider;
        }

        throw new ProviderMissingException('Package Service Provider not found');
    }

    /**
     * Resolve the correct Package files path.
     *
     * @param array $properties
     *
     * @return string
     */
    public function resolveClassPath($properties)
    {
        $path = $properties['path'];

        if ($properties['type'] == 'package') {
            $path .= 'src' .DS;
        }

        return $path;
    }

    /**
     * Dynamically pass methods to the repository.
     *
     * @param string $method
     * @param mixed  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->repository, $method), $arguments);
    }
}
