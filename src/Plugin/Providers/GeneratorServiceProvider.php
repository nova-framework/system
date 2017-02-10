<?php

namespace Nova\Plugin\Providers;

use Nova\Plugin\Generators\MakePluginCommand;
use Nova\Support\ServiceProvider;


class GeneratorServiceProvider extends ServiceProvider
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
        $this->registerMakePluginCommand();
    }

    /**
     * Register the make:plugin command.
     */
    private function registerMakePluginCommand()
    {
        $this->app->bindShared('command.make.plugin', function ($app) {
            return new MakePluginCommand($app['files'], $app['plugins']);
        });

        $this->commands('command.make.plugin');
    }

}
