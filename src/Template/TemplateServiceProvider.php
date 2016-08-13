<?php

namespace Nova\Template;

use Nova\Template\Factory;
use Nova\Support\ServiceProvider;


class TemplateServiceProvider extends ServiceProvider
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
        $this->app->bindShared('template', function($app)
        {
            return new Template($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('template');
    }
}
