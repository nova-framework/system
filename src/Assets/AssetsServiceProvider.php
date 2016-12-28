<?php

namespace Nova\Assets;

use Nova\Assets\Assets\Manager;
use Nova\Config\Config;
use Nova\Support\ServiceProvider;


class AssetsServiceProvider extends ServiceProvider
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
        $this->registerDispatcher();

        $this->registerManager();
    }

    /**
     * Register the Assets Dispatcher.
     */
    public function registerDispatcher()
    {
        // NOTE: When this method is executed, the Config Store is not yet available.
        $driver = Config::get('assets.driver', 'default');

        if ($driver == 'custom') {
            $className = Config::get('assets.dispatcher');
        } else {
            $className = 'Nova\Assets\Dispatch\\' .ucfirst($driver) .'Dispatcher';
        }

        // Bind the calculated class name to the Assets Dispatcher Interface.
        $this->app->bind('Nova\Assets\DispatcherInterface', $className);
    }

    /**
     * Register the Assets Manager.
     *
     * @return void
     */
    public function registerManager()
    {
        $this->app->bindShared('assets', function($app)
        {
            return new AssetsManager($app);
        });
    }

}
