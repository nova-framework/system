<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;


class ConsoleSupportServiceProvider extends ServiceProvider
{
    /**
     * The Provider Class names.
     *
     * @var array
     */
    protected $providers = array(
        'Nova\Foundation\Providers\CommandCreatorServiceProvider',
        'Nova\Foundation\Providers\ModelCreatorServiceProvider',
        'Nova\Foundation\Providers\ComposerServiceProvider',
        'Nova\Foundation\Providers\KeyGeneratorServiceProvider',
        'Nova\Foundation\Providers\MaintenanceServiceProvider',
        'Nova\Foundation\Providers\OptimizeServiceProvider',
        'Nova\Foundation\Providers\RouteListServiceProvider',
        'Nova\Foundation\Providers\ServerServiceProvider',
        'Nova\Foundation\Providers\TinkerServiceProvider',
        'Nova\Queue\FailConsoleServiceProvider',
    );

    /**
     * An array of the Service Provider instances.
     *
     * @var array
     */
    protected $instances = array();

    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->instances = array();

        foreach ($this->providers as $provider) {
            $this->instances[] = $this->app->register($provider);
        }
    }

    /**
     * Get the Services provided by the Provider.
     *
     * @return array
     */
    public function provides()
    {
        $provides = array();

        foreach ($this->providers as $provider) {
            $instance = $this->app->resolveProviderClass($provider);

            $provides = array_merge($provides, $instance->provides());
        }

        return $provides;
    }

}
