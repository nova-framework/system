<?php

namespace Nova\View;

use Nova\View\TemplateFactory;
use Nova\View\ViewFactory;
use Nova\Support\ServiceProvider;


class ViewServiceProvider extends ServiceProvider
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
        $this->app->bindShared('view', function($app)
        {
            return new Factory();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('view');
    }
}
