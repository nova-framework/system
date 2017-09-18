<?php

namespace Nova\Routing;

use Nova\Http\Request;
use Nova\Routing\Assets\Dispatcher as AssetDispatcher;
use Nova\Routing\ResponseFactory;
use Nova\Routing\Router;
use Nova\Routing\Redirector;
use Nova\Routing\UrlGenerator;
use Nova\Support\Facades\Config;
use Nova\Support\ServiceProvider;


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

        $this->registerAssetDispatcher();
    }

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function($app)
        {
            $router = new Router($app['events'], $app);

            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing') {
                //$router->disableFilters();
            }

            return $router;
        });
    }

    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app['url'] = $this->app->share(function($app)
        {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            $url = new UrlGenerator($routes, $app->rebinding('request', function($app, $request)
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
        $this->app['redirect'] = $this->app->share(function($app)
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
     * Register the Assets Dispatcher.
     *
     * @return void
     */
    protected function registerAssetDispatcher()
    {
        $this->app->bindShared('assets.dispatcher', function($app)
        {
            return new AssetDispatcher();
        });

        // Register the default Asset Routes to Dispatcher.
        $dispatcher = $this->app['assets.dispatcher'];

        $dispatcher->route('assets/(.*)', function (Request $request, $path) use ($dispatcher)
        {
            $path = base_path('assets') .DS .str_replace('/', DS, $path);

            return $dispatcher->serve($path, $request);
        });

        $dispatcher->route('packages/([^/]+)/(.*)', function (Request $request, $plugin, $path) use ($dispatcher)
        {
            if (! is_null($basePath = $dispatcher->findNamedPath($plugin))) {
                $path = $basePath .str_replace('/', DS, $path);

                return $dispatcher->serve($path, $request);
            }

            return new Response('File Not Found', 404);
        });
    }

}
