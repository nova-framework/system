<?php

namespace Nova\Database;

use Nova\Database\Console\Migrations\ResetCommand;
use Nova\Database\Console\Migrations\RefreshCommand;
use Nova\Database\Console\Migrations\InstallCommand;
use Nova\Database\Console\Migrations\MigrateCommand;
use Nova\Database\Console\Migrations\RollbackCommand;
use Nova\Database\Console\Migrations\MakeMigrationCommand;
use Nova\Database\Migrations\DatabaseMigrationRepository;
use Nova\Database\Migrations\Migrator;
use Nova\Database\Migrations\MigrationCreator;
use Nova\Support\ServiceProvider;


class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCommands();
    }

    /**
     * Register the migration repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->bindShared('migration.repository', function($app)
        {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Register the migrator service.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        $this->app->bindShared('migrator', function($app)
        {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register all of the migration commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $commands = array('Migrate', 'Rollback', 'Reset', 'Refresh', 'Install', 'Make');

        foreach ($commands as $command) {
            $this->{'register' .$command .'Command'}();
        }

        $this->commands(
            'command.migrate', 'command.migrate.make',
            'command.migrate.install', 'command.migrate.rollback',
            'command.migrate.reset', 'command.migrate.refresh'
        );
    }

    /**
     * Register the "migrate" migration command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->bindShared('command.migrate', function($app)
        {
            $packagePath = $app['path.base'] .DS .'vendor';

            return new MigrateCommand($app['migrator'], $packagePath);
        });
    }

    /**
     * Register the "rollback" migration command.
     *
     * @return void
     */
    protected function registerRollbackCommand()
    {
        $this->app->bindShared('command.migrate.rollback', function($app)
        {
            return new RollbackCommand($app['migrator']);
        });
    }

    /**
     * Register the "reset" migration command.
     *
     * @return void
     */
    protected function registerResetCommand()
    {
        $this->app->bindShared('command.migrate.reset', function($app)
        {
            return new ResetCommand($app['migrator']);
        });
    }

    /**
     * Register the "refresh" migration command.
     *
     * @return void
     */
    protected function registerRefreshCommand()
    {
        $this->app->bindShared('command.migrate.refresh', function($app)
        {
            return new RefreshCommand;
        });
    }

    /**
     * Register the "install" migration command.
     *
     * @return void
     */
    protected function registerInstallCommand()
    {
        $this->app->bindShared('command.migrate.install', function($app)
        {
            return new InstallCommand($app['migration.repository']);
        });
    }

    /**
     * Register the "install" migration command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->app->bindShared('migration.creator', function($app)
        {
            return new MigrationCreator($app['files']);
        });

        $this->app->bindShared('command.migrate.make', function($app)
        {
            $creator = $app['migration.creator'];

            $packagePath = $app['path.base'] .DS .'vendor';

            return new MakeMigrationCommand($creator, $packagePath);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'migrator', 'migration.repository', 'command.migrate',
            'command.migrate.rollback', 'command.migrate.reset',
            'command.migrate.refresh', 'command.migrate.install',
            'migration.creator', 'command.migrate.make',
        );
    }

}
