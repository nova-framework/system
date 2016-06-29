<?php

namespace Nova\Modules\Providers;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{
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
        $this->registerDisableCommand();
        $this->registerEnableCommand();
        $this->registerListCommand();
        $this->registerOptimizeCommand();
    }

    /**
     * Register the module:disable command.
     */
    protected function registerDisableCommand()
    {
        $this->app->singleton('command.module.disable', function () {
            return new \Nova\Modules\Console\Commands\ModuleDisableCommand();
        });

        $this->commands('command.module.disable');
    }

    /**
     * Register the module:enable command.
     */
    protected function registerEnableCommand()
    {
        $this->app->singleton('command.module.enable', function () {
            return new \Nova\Modules\Console\Commands\ModuleEnableCommand();
        });

        $this->commands('command.module.enable');
    }

    /**
     * Register the module:list command.
     */
    protected function registerListCommand()
    {
        $this->app->singleton('command.module.list', function ($app) {
            return new \Nova\Modules\Console\Commands\ModuleListCommand($app['modules']);
        });

        $this->commands('command.module.list');
    }


    /**
     * Register the module:optimize command.
     */
    protected function registerOptimizeCommand()
    {
        $this->app->singleton('command.module.optimize', function () {
            return new \Nova\Modules\Console\Commands\ModuleOptimizeCommand();
        });

        $this->commands('command.module.optimize');
    }
}
