<?php

namespace Nova\Module\Providers;

use Nova\Module\Generators\MakeModuleCommand;
use Nova\Module\Generators\MakeControllerCommand;
use Nova\Module\Generators\MakeMiddlewareCommand;
use Nova\Module\Generators\MakeModelCommand;
use Nova\Module\Generators\MakePolicyCommand;
use Nova\Module\Generators\MakeRequestCommand;
use Nova\Module\Generators\MakeMigrationCommand;
use Nova\Module\Generators\MakeSeederCommand;
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
        $commands = array('MakeModule', 'MakeController', 'MakeModel');

        foreach ($commands as $command) {
            $this->{'register' .$command .'Command'}();
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

}
