<?php

namespace Nova\Modules\Support\Providers;

use Nova\Support\ServiceProvider;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = array();


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    protected function bootstrapFrom($path)
    {
        $app = $this->app;

        return require $path;
    }

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

}
