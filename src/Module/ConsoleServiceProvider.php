<?php

namespace Nova\Module;

use Nova\Module\Console\ModuleListCommand;
use Nova\Module\Console\ModuleMigrateCommand;
use Nova\Module\Console\ModuleMigrateRefreshCommand;
use Nova\Module\Console\ModuleMigrateResetCommand;
use Nova\Module\Console\ModuleMigrateRollbackCommand;
use Nova\Module\Console\ModuleMigrateStatusCommand;
use Nova\Module\Console\ModuleSeedCommand;
use Nova\Module\Console\ModuleOptimizeCommand;

use Nova\Module\Console\ModuleMakeCommand;
use Nova\Module\Console\ConsoleMakeCommand;
use Nova\Module\Console\ControllerMakeCommand;
use Nova\Module\Console\EventMakeCommand;
use Nova\Module\Console\ListenerMakeCommand;
use Nova\Module\Console\MiddlewareMakeCommand;
use Nova\Module\Console\MigrationMakeCommand;
use Nova\Module\Console\ModelMakeCommand;
use Nova\Module\Console\PolicyMakeCommand;
use Nova\Module\Console\ProviderMakeCommand;
use Nova\Module\Console\SeederMakeCommand;

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
            'ModuleList',
            'ModuleMigrate',
            'ModuleMigrateRefresh',
            'ModuleMigrateReset',
            'ModuleMigrateRollback',
            'ModuleMigrateStatus',
            'ModuleOptimize',
            'ModuleSeed',

            // Generators
            'ModuleMake',
            'ConsoleMake',
            'ControllerMake',
            'EventMake',
            'ListenerMake',
            'MiddlewareMake',
            'ModelMake',
            'PolicyMake',
            'ProviderMake',
            'MigrationMake',
            'SeederMake'
        );

        foreach ($commands as $command) {
            $method = 'register' .$command .'Command';

            call_user_func(array($this, $method));
        }
    }

    /**
     * Register the module:list command.
     */
    protected function registerModuleListCommand()
    {
        $this->app->singleton('command.module.list', function ($app) {
            return new ModuleListCommand($app['modules']);
        });

        $this->commands('command.module.list');
    }

    /**
     * Register the module:migrate command.
     */
    protected function registerModuleMigrateCommand()
    {
        $this->app->singleton('command.module.migrate', function ($app) {
            return new ModuleMigrateCommand($app['migrator'], $app['modules']);
        });

        $this->commands('command.module.migrate');
    }

    /**
     * Register the module:migrate:refresh command.
     */
    protected function registerModuleMigrateRefreshCommand()
    {
        $this->app->singleton('command.module.migrate.refresh', function ($app) {
            return new ModuleMigrateRefreshCommand($app['modules']);
        });

        $this->commands('command.module.migrate.refresh');
    }

    /**
     * Register the module:migrate:reset command.
     */
    protected function registerModuleMigrateResetCommand()
    {
        $this->app->singleton('command.module.migrate.reset', function ($app) {
            return new ModuleMigrateResetCommand($app['modules'], $app['files'], $app['migrator']);
        });

        $this->commands('command.module.migrate.reset');
    }

    /**
     * Register the module:migrate:rollback command.
     */
    protected function registerModuleMigrateRollbackCommand()
    {
        $this->app->singleton('command.module.migrate.rollback', function ($app) {
            return new ModuleMigrateRollbackCommand($app['migrator'], $app['modules']);
        });

        $this->commands('command.module.migrate.rollback');
    }

    /**
     * Register the "status" migration command.
     *
     * @return void
     */
    protected function registerModuleMigrateStatusCommand()
    {
        $this->app->bindShared('command.module.migrate.status', function ($app)
        {
            return new ModuleMigrateStatusCommand($app['migrator'], $app['modules']);
        });

        $this->commands('command.module.migrate.status');
    }

    /**
     * Register the module:seed command.
     */
    protected function registerModuleSeedCommand()
    {
        $this->app->singleton('command.module.seed', function ($app) {
            return new ModuleSeedCommand($app['modules']);
        });

        $this->commands('command.module.seed');
    }

    /**
     * Register the module:list command.
     */
    protected function registerModuleOptimizeCommand()
    {
        $this->app->singleton('command.module.optimize', function ($app) {
            return new ModuleOptimizeCommand($app['modules']);
        });

        $this->commands('command.module.optimize');
    }

    /**
     * Register the make:module command.
     */
    private function registerModuleMakeCommand()
    {
        $this->app->bindShared('command.make.module', function ($app)
        {
            return new ModuleMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerConsoleMakeCommand()
    {
        $this->app->bindShared('command.make.module.console', function ($app)
        {
            return new ConsoleMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.console');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerControllerMakeCommand()
    {
        $this->app->bindShared('command.make.module.controller', function ($app)
        {
            return new ControllerMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.controller');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerEventMakeCommand()
    {
        $this->app->bindShared('command.make.module.event', function ($app)
        {
            return new EventMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.event');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerListenerMakeCommand()
    {
        $this->app->bindShared('command.make.module.listener', function ($app)
        {
            return new ListenerMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.listener');
    }

    /**
     * Register the make:module:controller command.
     */
    private function registerMiddlewareMakeCommand()
    {
        $this->app->bindShared('command.make.module.middleware', function ($app)
        {
            return new MiddlewareMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.middleware');
    }

    /**
     * Register the make:module:model command.
     */
    private function registerModelMakeCommand()
    {
        $this->app->bindShared('command.make.module.model', function ($app)
        {
            return new ModelMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.model');
    }

    /**
     * Register the make:module:policy command.
     */
    private function registerPolicyMakeCommand()
    {
        $this->app->bindShared('command.make.module.policy', function ($app)
        {
            return new PolicyMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.policy');
    }

    /**
     * Register the make:module:provider command.
     */
    private function registerProviderMakeCommand()
    {
        $this->app->bindShared('command.make.module.provider', function ($app)
        {
            return new ProviderMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.module.provider');
    }

    /**
     * Register the make:plugin:migration command.
     */
    private function registerMigrationMakeCommand()
    {
        $this->app->bindShared('command.make.plugin.migration', function ($app)
        {
            return new MigrationMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.plugin.migration');
    }

    /**
     * Register the make:plugin:seeder command.
     */
    private function registerSeederMakeCommand()
    {
        $this->app->bindShared('command.make.plugin.seeder', function ($app)
        {
            return new SeederMakeCommand($app['files'], $app['modules']);
        });

        $this->commands('command.make.plugin.seeder');
    }
}
