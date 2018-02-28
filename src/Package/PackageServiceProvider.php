<?php

namespace Nova\Package;

use Nova\Package\PackageManager;
use Nova\Package\Repository;
use Nova\Support\ServiceProvider;


class PackageServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the Provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the Service Provider.
     */
    public function boot()
    {
        $packages = $this->app['packages'];

        $packages->register();
    }

    /**
     * Register the Service Provider.
     */
    public function register()
    {
        $this->app->bindShared('packages', function ($app)
        {
            $repository = new Repository($app['config'], $app['files']);

            return new PackageManager($app, $repository);
        });
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return string
     */
    public function provides()
    {
        return array('packages');
    }

}
