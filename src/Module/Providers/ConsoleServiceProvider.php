<?php

namespace Nova\Module\Providers;

use Nova\Module\Console\ModuleListCommand;
use Nova\Module\Console\ModuleMigrateCommand;
use Nova\Module\Console\ModuleMigrateRefreshCommand;
use Nova\Module\Console\ModuleMigrateResetCommand;
use Nova\Module\Console\ModuleMigrateRollbackCommand;
use Nova\Module\Console\ModuleMigrateStatusCommand;
use Nova\Module\Console\ModuleSeedCommand;
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
            'ModuleList',
            'ModuleMigrate',
            'ModuleMigrateRefresh',
            'ModuleMigrateReset',
            'ModuleMigrateRollback',
            'ModuleMigrateStatus',
            'ModuleOptimize',
            'ModuleSeed',
        );

        foreach ($commands as $command) {
            $this->{'register' .$command .'Command'}();
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
}
