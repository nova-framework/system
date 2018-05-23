<?php

namespace Nova\Assets;

use Nova\Assets\AssetDispatcher;
use Nova\Assets\AssetManager;
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
        $this->app->bindShared('assets.dispatcher', function ($app)
        {
            $dispatcher = new AssetDispatcher($app);

            // Register the route for assets from main assets folder.
            $dispatcher->route('assets/(.*)', function (Request $request, $path) use ($dispatcher)
            {
                $basePath = $this->app['config']->get('routing.assets.path', base_path('assets'));

                $path = $basePath .DS .str_replace('/', DS, $path);

                return $dispatcher->serve($path, $request);
            });

            // Register the route for assets from Packages, Modules and Themes.
            $dispatcher->route('packages/([^/]+)/([^/]+)/(.*)', function (Request $request, $vendor, $package, $path) use ($dispatcher)
            {
                $namespace = $vendor .'/' .$package;

                if (is_null($packagePath = $dispatcher->getPackagePath($namespace))) {
                    return new Response('File Not Found', 404);
                }

                $path = $packagePath .str_replace('/', DS, $path);

                return $dispatcher->serve($path, $request);
            });

            // Register the route for assets from Vendor.
            $dispatcher->route('vendor/(.*)', function (Request $request, $path) use ($dispatcher)
            {
                $paths = $dispatcher->getVendorPaths();

                if (! Str::startsWith($path, $paths)) {
                    return new Response('File Not Found', 404);
                }

                $path = BASEPATH .'vendor' .DS .str_replace('/', DS, $path);

                return $dispatcher->serve($path, $request);
            });

            return $dispatcher;
        });
    }
}
