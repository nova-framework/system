<?php

namespace Nova\Packages\Support\Providers;

use Nova\Packages\Support\Providers\PackageServiceProvider as ServiceProvider;


class ThemeServiceProvider extends ServiceProvider
{

    /**
     * Register the package's assets.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @param  string  $path
     * @return void
     */
    protected function registerPackageAssets($package, $namespace, $path)
    {
        $assets = $path .DS .'Assets';

        if ($this->app['files']->isDirectory($assets)) {
            $namespace = 'themes/' .str_replace('_', '-', $namespace);

            $this->app['assets.dispatcher']->package($package, $assets, $namespace);
        }
    }
}
