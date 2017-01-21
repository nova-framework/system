<?php

namespace Nova\Module\Providers;

use Nova\Module\Console\Generators\MakeModuleCommand;
use Nova\Module\Console\Generators\MakeControllerCommand;
use Nova\Module\Console\Generators\MakeModelCommand;
use Nova\Module\Console\Generators\MakePolicyCommand;
use Nova\Module\Console\Generators\MakeMigrationCommand;
use Nova\Module\Console\Generators\MakeSeederCommand;
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
        $commands = array('MakeModule', 'MakeController', 'MakeModel', 'MakePolicy', 'MakeMigration', 'MakeSeeder');

        foreach ($commands as $command) {
            $this->{'register' .$command .'Command'}();
        }
    }

    /**
     * Register the make:module command.
     */
    private function registerMakeModuleCommand()
    {
        $this->app->bindShared('command.make.module', function ($app) {
            return new MakeModuleCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerMakeControllerCommand()
    {
        $this->app->bindShared('command.make.module.controller', function ($app) {
            return new MakeControllerCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.controller');
    }

    /**
     * Register the make:module:model command.
     */
    private function registerMakeModelCommand()
    {
        $this->app->bindShared('command.make.module.model', function ($app) {
            return new MakeModelCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.model');
    }

    /**
     * Register the make:module:policy command.
     */
    private function registerMakePolicyCommand()
    {
        $this->app->bindShared('command.make.module.policy', function ($app) {
            return new MakePolicyCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.policy');
    }

    /**
     * Register the make:module:migration command.
     */
    private function registerMakeMigrationCommand()
    {
        $this->app->bindShared('command.make.module.migration', function ($app) {
            return new MakeMigrationCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.migration');
    }

    /**
     * Register the make:module:seeder command.
     */
    private function registerMakeSeederCommand()
    {
        $this->app->bindShared('command.make.module.seeder', function ($app) {
            return new MakeSeederCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.seeder');
    }
}
