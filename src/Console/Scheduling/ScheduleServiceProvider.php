<?php

namespace Nova\Console\Scheduling;

use Nova\Support\ServiceProvider;


class ScheduleServiceProvider extends ServiceProvider
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
        $this->commands('Nova\Console\Scheduling\ScheduleRunCommand');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Nova\Console\Scheduling\ScheduleRunCommand',
        );
    }
}
