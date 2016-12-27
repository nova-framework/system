<?php

namespace Nova\Assets;

use Nova\Config\Config;
use Nova\Support\ServiceProvider;


class AssetsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDispatcher();
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
            $className = 'Nova\Assets\\' .ucfirst($driver) .'Dispatcher';
        }

        // Bind the calculated class name to the Assets Dispatcher Interface.
        $this->app->bind('Nova\Assets\DispatcherInterface', $className);
    }

}
