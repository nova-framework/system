<?php

namespace Nova\Foundation\Providers;

use Nova\Support\Composer;
use Nova\Support\ServiceProvider;


class ComposerServiceProvider extends ServiceProvider
{
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
        $this->app->bindShared('composer', function($app)
        {
            return new Composer($app['files'], $app['path.base']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('composer');
    }

}
