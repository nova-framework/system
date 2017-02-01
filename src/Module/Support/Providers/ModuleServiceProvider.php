<?php

namespace Nova\Module\Support\Providers;

use Nova\Support\ServiceProvider;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Load the standard config file for the module.
     *
     * @return void
     */
    protected function loadConfig()
    {
        //
    }

    /**
     * Load the standard config file for the module.
     *
     * @param  string  $path
     * @return mixed
     */
    protected function loadConfigFrom($path)
    {
        return require $path;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

}
