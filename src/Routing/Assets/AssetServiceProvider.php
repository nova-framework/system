<?php

namespace Nova\Routing\Assets;

use Nova\Routing\Assets\AssetDispatcher;
use Nova\Routing\Assets\AssetManager;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Support\ServiceProvider;
use Nova\Support\Str;


class AssetServiceProvider extends ServiceProvider
{

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAssetManager();

        $this->registerAssetDispatcher();
    }

    /**
     * Register the Asset Manager instance.
     *
     * @return void
     */
    protected function registerAssetManager()
    {
        $this->app->singleton('assets', function ($app)
        {
            return new AssetManager($app['view']);
        });
    }

    /**
     * Register the Assets Dispatcher.
     *
     * @return void
     */
    protected function registerAssetDispatcher()
    {
        $this->app->singleton('assets.dispatcher', function ($app)
        {
            return new AssetDispatcher($app);
        });
    }
}
