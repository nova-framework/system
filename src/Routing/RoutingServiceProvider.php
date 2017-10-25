<?php

namespace Nova\Routing;

use Nova\Http\Request;
use Nova\Filesystem\Filesystem;
use Nova\Routing\Assets\Dispatcher as AssetDispatcher;
use Nova\Routing\ControllerDispatcher;
use Nova\Routing\ResponseFactory;
use Nova\Routing\Router;
use Nova\Routing\Redirector;
use Nova\Routing\UrlGenerator;
use Nova\Support\ServiceProvider;
use Nova\Support\Str;


class RoutingServiceProvider extends ServiceProvider
{

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();

        $this->registerUrlGenerator();

        $this->registerRedirector();

        $this->registerResponseFactory();

        $this->registerControllerDispatcher();

        $this->registerAssetDispatcher();
    }

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app)
        {
            return new Router($app['events'], $app);
        });
    }

    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('url', function ($app)
        {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            $url = new UrlGenerator($routes, $app->rebinding('request', function ($app, $request)
            {
                $app['url']->setRequest($request);
            }));

            $url->setSessionResolver(function ()
            {
                return $this->app['session'];
            });

            return $url;
        });
    }

    /**
     * Register the Redirector service.
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app)
        {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });
    }

    /**
     * Register the response factory implementation.
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->singleton('response.factory', function ($app)
        {
            return new ResponseFactory();
        });
    }

    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerControllerDispatcher()
    {
        $this->app->singleton('routing.controller.dispatcher', function ($app)
        {
            return new ControllerDispatcher($app);
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
            return new AssetDispatcher($app);
        });

        // Register the default Asset Routes to Dispatcher.
        $dispatcher = $this->app['assets.dispatcher'];

        // For the assets from Vendor or main assets folder.
        $dispatcher->route('(assets|vendor)/(.*)', function (Request $request, $type, $path) use ($dispatcher)
        {
            if ($type == 'vendor') {
                $paths = $dispatcher->getPaths();

                if (! Str::startsWith($path, $paths)) {
                    return new Response('File Not Found', 404);
                }
            }

            $path = ROOTDIR .$type .DS .str_replace('/', DS, $path);

            return $dispatcher->serve($path, $request);
        });

        // For assets from Modules and Themes.
        $dispatcher->route('(themes|modules)/([^/]+)/assets/(.*)', function (Request $request, $type, $folder, $path) use ($dispatcher)
        {
            if (strlen($folder) > 3) {
                $folder = Str::studly($folder);
            } else {
                $folder = Str::upper($folder);
            }

            $type = (strtolower($type) == 'modules') ? 'Modules' : 'Themes';

            $path = APPDIR .$type .DS .$folder .DS .'Assets' .DS .str_replace('/', DS, $path);

            return $dispatcher->serve($path, $request);
        });
    }
}
