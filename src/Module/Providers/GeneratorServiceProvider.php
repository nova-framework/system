<?php

namespace Nova\Module\Providers;

use Nova\Module\Console\MakeModuleCommand;
use Nova\Module\Console\MakeConsoleCommand;
use Nova\Module\Console\MakeControllerCommand;
use Nova\Module\Console\MakeModelCommand;
use Nova\Module\Console\MakePolicyCommand;
use Nova\Module\Console\MakeProviderCommand;
use Nova\Support\ServiceProvider;


class GeneratorServiceProvider extends ServiceProvider
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
        $commands = array('MakeModule', 'MakeConsole', 'MakeController', 'MakeModel', 'MakePolicy', 'MakeProvider');

        foreach ($commands as $command) {
            $method = 'register' .$command .'Command';

            call_user_func(array($this, $method));
        }
    }

    /**
     * Register the make:module command.
     */
    private function registerMakeModuleCommand()
    {
        $this->app->bindShared('command.make.module', function ($app)
        {
            return new MakeModuleCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerMakeConsoleCommand()
    {
        $this->app->bindShared('command.make.module.console', function ($app)
        {
            return new MakeConsoleCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.console');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerMakeControllerCommand()
    {
        $this->app->bindShared('command.make.module.controller', function ($app)
        {
            return new MakeControllerCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.controller');
    }

    /**
     * Register the make:module:model command.
     */
    private function registerMakeModelCommand()
    {
        $this->app->bindShared('command.make.module.model', function ($app)
        {
            return new MakeModelCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.model');
    }

    /**
     * Register the make:module:policy command.
     */
    private function registerMakePolicyCommand()
    {
        $this->app->bindShared('command.make.module.policy', function ($app)
        {
            return new MakePolicyCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.policy');
    }

    /**
     * Register the make:module:provider command.
     */
    private function registerMakeProviderCommand()
    {
        $this->app->bindShared('command.make.module.provider', function ($app)
        {
            return new MakeProviderCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.provider');
    }
}
