<?php

namespace Nova\Packages;

use Nova\Packages\Console\PackageListCommand;
use Nova\Packages\Console\PackageMigrateCommand;
use Nova\Packages\Console\PackageMigrateRefreshCommand;
use Nova\Packages\Console\PackageMigrateResetCommand;
use Nova\Packages\Console\PackageMigrateRollbackCommand;
use Nova\Packages\Console\PackageMigrateStatusCommand;
use Nova\Packages\Console\PackageSeedCommand;
use Nova\Packages\Console\PackageOptimizeCommand;

use Nova\Packages\Console\PackageMakeCommand;
use Nova\Packages\Console\ConsoleMakeCommand;
use Nova\Packages\Console\ControllerMakeCommand;
use Nova\Packages\Console\EventMakeCommand;
use Nova\Packages\Console\JobMakeCommand;
use Nova\Packages\Console\ListenerMakeCommand;
use Nova\Packages\Console\MiddlewareMakeCommand;
use Nova\Packages\Console\MigrationMakeCommand;
use Nova\Packages\Console\ModelMakeCommand;
use Nova\Packages\Console\NotificationMakeCommand;
use Nova\Packages\Console\PolicyMakeCommand;
use Nova\Packages\Console\ProviderMakeCommand;
use Nova\Packages\Console\RequestMakeCommand;
use Nova\Packages\Console\SeederMakeCommand;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     */
    public function register()
    {
        $commands = array(
            'PackageList',
            'PackageMigrate',
            'PackageMigrateRefresh',
            'PackageMigrateReset',
            'PackageMigrateRollback',
            'PackageMigrateStatus',
            'PackageSeed',
            'PackageOptimize',

            //
            'PackageMake',
            'ConsoleMake',
            'ControllerMake',
            'EventMake',
            'JobMake',
            'ListenerMake',
            'MiddlewareMake',
            'ModelMake',
            'NotificationMake',
            'PolicyMake',
            'ProviderMake',
            'MigrationMake',
            'RequestMake',
            'SeederMake',
        );

        foreach ($commands as $command) {
            $method = 'register' .$command .'Command';

            call_user_func(array($this, $method));
        }
    }

    /**
     * Register the Package:list command.
     */
    protected function registerPackageListCommand()
    {
        $this->app->singleton('command.package.list', function ($app)
        {
            return new PackageListCommand($app['packages']);
        });

        $this->commands('command.package.list');
    }

    /**
     * Register the Package:migrate command.
     */
    protected function registerPackageMigrateCommand()
    {
        $this->app->singleton('command.package.migrate', function ($app)
        {
            return new PackageMigrateCommand($app['migrator'], $app['packages']);
        });

        $this->commands('command.package.migrate');
    }

    /**
     * Register the Package:migrate:refresh command.
     */
    protected function registerPackageMigrateRefreshCommand()
    {
        $this->app->singleton('command.package.migrate.refresh', function ($app)
        {
            return new PackageMigrateRefreshCommand($app['packages']);
        });

        $this->commands('command.package.migrate.refresh');
    }

    /**
     * Register the Package:migrate:reset command.
     */
    protected function registerPackageMigrateResetCommand()
    {
        $this->app->singleton('command.package.migrate.reset', function ($app)
        {
            return new PackageMigrateResetCommand($app['packages'], $app['files'], $app['migrator']);
        });

        $this->commands('command.package.migrate.reset');
    }

    /**
     * Register the Package:migrate:rollback command.
     */
    protected function registerPackageMigrateRollbackCommand()
    {
        $this->app->singleton('command.package.migrate.rollback', function ($app)
        {
            return new PackageMigrateRollbackCommand($app['migrator'], $app['packages']);
        });

        $this->commands('command.package.migrate.rollback');
    }

    /**
     * Register the Package:migrate:reset command.
     */
    protected function registerPackageMigrateStatusCommand()
    {
        $this->app->singleton('command.package.migrate.status', function ($app)
        {
            return new PackageMigrateStatusCommand($app['migrator'], $app['packages']);
        });

        $this->commands('command.package.migrate.status');
    }

    /**
     * Register the Package:seed command.
     */
    protected function registerPackageSeedCommand()
    {
        $this->app->singleton('command.package.seed', function ($app)
        {
            return new PackageSeedCommand($app['packages']);
        });

        $this->commands('command.package.seed');
    }

    /**
     * Register the module:list command.
     */
    protected function registerPackageOptimizeCommand()
    {
        $this->app->singleton('command.package.optimize', function ($app)
        {
            return new PackageOptimizeCommand($app['packages']);
        });

        $this->commands('command.package.optimize');
    }

    /**
     * Register the make:package command.
     */
    private function registerPackageMakeCommand()
    {
        $this->app->bindShared('command.make.package', function ($app)
        {
            return new PackageMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package');
    }

    /**
     * Register the make:package:console command.
     */
    private function registerConsoleMakeCommand()
    {
        $this->app->bindShared('command.make.package.console', function ($app)
        {
            return new ConsoleMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.console');
    }

    /**
     * Register the make:package:controller command.
     */
    private function registerControllerMakeCommand()
    {
        $this->app->bindShared('command.make.package.controller', function ($app)
        {
            return new ControllerMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.controller');
    }

    /**
     * Register the make:package:event command.
     */
    private function registerEventMakeCommand()
    {
        $this->app->bindShared('command.make.package.event', function ($app)
        {
            return new EventMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.event');
    }

    /**
     * Register the make:package:job command.
     */
    private function registerJobMakeCommand()
    {
        $this->app->bindShared('command.make.package.job', function ($app)
        {
            return new JobMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.job');
    }

    /**
     * Register the make:package:listener command.
     */
    private function registerListenerMakeCommand()
    {
        $this->app->bindShared('command.make.package.listener', function ($app)
        {
            return new ListenerMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.listener');
    }

    /**
     * Register the make:package:middleware command.
     */
    private function registerMiddlewareMakeCommand()
    {
        $this->app->bindShared('command.make.package.middleware', function ($app)
        {
            return new MiddlewareMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.middleware');
    }

    /**
     * Register the make:package:model command.
     */
    private function registerModelMakeCommand()
    {
        $this->app->bindShared('command.make.package.model', function ($app)
        {
            return new ModelMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.model');
    }

    /**
     * Register the make:package:notification command.
     */
    private function registerNotificationMakeCommand()
    {
        $this->app->bindShared('command.make.package.notification', function ($app)
        {
            return new NotificationMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.notification');
    }

    /**
     * Register the make:package:policy command.
     */
    private function registerPolicyMakeCommand()
    {
        $this->app->bindShared('command.make.package.policy', function ($app)
        {
            return new PolicyMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.policy');
    }

    /**
     * Register the make:module:provider command.
     */
    private function registerProviderMakeCommand()
    {
        $this->app->bindShared('command.make.package.provider', function ($app)
        {
            return new ProviderMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.provider');
    }

    /**
     * Register the make:package:migration command.
     */
    private function registerMigrationMakeCommand()
    {
        $this->app->bindShared('command.make.package.migration', function ($app)
        {
            return new MigrationMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.migration');
    }

    /**
     * Register the make:module:provider command.
     */
    private function registerRequestMakeCommand()
    {
        $this->app->bindShared('command.make.package.request', function ($app)
        {
            return new RequestMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.request');
    }

    /**
     * Register the make:package:seeder command.
     */
    private function registerSeederMakeCommand()
    {
        $this->app->bindShared('command.make.package.seeder', function ($app)
        {
            return new SeederMakeCommand($app['files'], $app['packages']);
        });

        $this->commands('command.make.package.seeder');
    }
}
