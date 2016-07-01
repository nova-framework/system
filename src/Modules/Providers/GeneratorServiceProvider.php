<?php

namespace Nova\Modules\Providers;

use Nova\Modules\Console\Generators\MakeModuleCommand;
use Nova\Modules\Console\Generators\MakeControllerCommand;
use Nova\Modules\Console\Generators\MakeModelCommand;
use Nova\Modules\Console\Generators\MakeMigrationCommand;
use Nova\Modules\Console\Generators\MakeSeederCommand;
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
        $commands = array('MakeModule', 'MakeController', 'MakeModel', 'MakeMigration', 'MakeSeeder');

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
            return new MakeModuleCommand();
        });

        $this->commands('command.make.module');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerMakeControllerCommand()
    {
        $this->app->bindShared('command.make.module.controller', function ($app) {
            return new MakeControllerCommand();
        });

        $this->commands('command.make.module.controller');
    }

    /**
     * Register the make:module:model command.
     */
    private function registerMakeModelCommand()
    {
        $this->app->bindShared('command.make.module.model', function ($app) {
            return new MakeModelCommand();
        });

        $this->commands('command.make.module.model');
    }

    /**
     * Register the make:module:migration command.
     */
    private function registerMakeMigrationCommand()
    {
        $this->app->bindShared('command.make.module.migration', function ($app) {
            return new MakeMigrationCommand();
        });

        $this->commands('command.make.module.migration');
    }

    /**
     * Register the make:module:seeder command.
     */
    private function registerMakeSeederCommand()
    {
        $this->app->bindShared('command.make.module.seeder', function ($app) {
            return new MakeSeederCommand();
        });

        $this->commands('command.make.module.seeder');
    }
}
