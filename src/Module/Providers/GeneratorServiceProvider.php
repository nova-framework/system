<?php

namespace Nova\Module\Providers;

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
        $commands = array(
            'Module',
            'Console',
            'Controller',
            'Event',
            'Listener',
            'Middleware',
            'Model',
            'Policy',
            'Provider',
            'Migration',
            'Seeder'
        );

        foreach ($commands as $command) {
            $method = 'register' .$command .'MakeCommand';

            call_user_func(array($this, $method));
        }
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
