<?php

namespace Nova\Package\Support\Providers;

use Nova\Auth\Contracts\Access\GateInterface as Gate;
use Nova\Support\ServiceProvider;


class PackageServiceProvider extends ServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = array();


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    protected function bootstrapFrom($path)
    {
        $app = $this->app;

        return require $path;
    }
}
