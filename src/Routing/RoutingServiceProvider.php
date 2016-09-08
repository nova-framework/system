<?php

namespace Nova\Routing;

use Nova\Config\Config;
use Nova\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAssetsDispatcher();

        $this->registerRouter();

        $this->registerUrlGenerator();

        $this->registerRedirector();
    }

    /**
     * Register the Assets Dispatcher.
     */
    public function registerAssetsDispatcher()
    {
        // NOTE: When this method is executed, the Config Store is not yet available.
        $driver = Config::get('routing.assets.driver');

        if ($driver == 'custom') {
            $className = Config::get('routing.assets.dispatcher');
        } else {
            $className = 'Nova\Routing\Assets\\' .ucfirst($driver) .'Dispatcher';
        }

        // Bind the calculated class name to the Assets Dispatcher Interface.
        $this->app->bind('Nova\Routing\Assets\DispatcherInterface', $className);
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

            return new UrlGenerator($routes, $app->rebinding('request', function($app, $request)
            {
                $app['url']->setRequest($request);
            }));
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

}
