<?php

namespace Nova\Assets;

use Nova\Assets\AssetManager;
use Nova\Support\ServiceProvider;


class AssetServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['assets']->cleanup();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('assets', function($app)
        {
            return new AssetManager($app);
        });
    }

}
