<?php

namespace Nova\Module;

use Nova\Module\Repository;
use Nova\Support\ServiceProvider;


class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the Application Events.
     * @return void
     */
    public function boot()
    {
        // Register the Commands
        $this->bootCommands();

        $modules = $this->app['modules'];

        // Autoscan for the Modules, if that was specified.
        $modules->start();

        // Register all Modules.
        $modules->register();
    }

    /**
     * Register the Service Provider.
     * @return void
     */
    public function register()
    {
        // Register IoC bindings
        $this->app['modules'] = $this->app->share(function($app)
        {
            return new Repository($app, $app['files'], $app['config']);
        });
    }

    /**
     * Register all available commands
     * @return void
     */
    public function bootCommands()
    {
        // Add the Modules List command
        $this->app['modules.list'] = $this->app->share(function($app)
        {
            return new Console\ModuleListCommand($app);
        });

        // Add the Scan command
        $this->app['modules.scan'] = $this->app->share(function($app)
        {
            return new Console\ModuleScanCommand($app);
        });

        // Add the Migrate command
        $this->app['modules.migrate'] = $this->app->share(function($app)
        {
            return new Console\ModuleMigrateCommand($app);
        });

        // Add the Seed command
        $this->app['modules.seed'] = $this->app->share(function($app)
        {
            return new Console\ModuleSeedCommand($app);
        });

        // Add the Module Make command
        $this->app['modules.create'] = $this->app->share(function($app)
        {
            return new Console\ModuleMakeCommand($app);
        });

        // Now register all the commands
        $this->commands(array(
            'modules.list',
            'modules.scan',
            'modules.migrate',
            'modules.seed',
            'modules.create',
        ));
    }

    /**
     * Provided service
     * @return array
     */
    public function provides()
    {
        return array('modules');
    }

}
