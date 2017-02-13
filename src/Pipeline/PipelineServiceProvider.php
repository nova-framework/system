<?php

namespace Nova\Pipeline;

use Nova\Support\ServiceProvider;


class PipelineServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Nova\Pipeline\Contracts\HubInterface', 'Nova\Pipeline\Hub');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Nova\Pipeline\Contracts\HubInterface',
        );
    }
}
