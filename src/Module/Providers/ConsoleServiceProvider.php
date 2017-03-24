<?php

namespace Nova\Module\Providers;

use Nova\Module\Console\ModuleListCommand;
use Nova\Module\Console\ModuleOptimizeCommand;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $commands = array(
            'List',
            'Optimize'
        );

        foreach ($commands as $command) {
            $this->{'register' .$command .'Command'}();
        }
    }

    /**
     * Register the module:list command.
     */
    protected function registerListCommand()
    {
        $this->app->singleton('command.module.list', function ($app) {
            return new ModuleListCommand($app['modules']);
        });

        $this->commands('command.module.list');
    }

    /**
     * Register the module:list command.
     */
    protected function registerOptimizeCommand()
    {
        $this->app->singleton('command.module.optimize', function ($app) {
            return new ModuleOptimizeCommand($app['modules']);
        });

        $this->commands('command.module.optimize');
    }
}
