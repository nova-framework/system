<?php

namespace Nova\Foundation\Providers;

use Nova\Foundation\Console\UpCommand;
use Nova\Foundation\Console\DownCommand;
use Nova\Foundation\Console\ServeCommand;
use Nova\Foundation\Console\TinkerCommand;
use Nova\Foundation\Console\JobMakeCommand;
use Nova\Foundation\Console\OptimizeCommand;
use Nova\Foundation\Console\RouteListCommand;
use Nova\Foundation\Console\EventMakeCommand;
use Nova\Foundation\Console\ModelMakeCommand;
use Nova\Foundation\Console\ViewClearCommand;
use Nova\Foundation\Console\PolicyMakeCommand;
use Nova\Foundation\Console\CommandMakeCommand;
use Nova\Foundation\Console\ConsoleMakeCommand;
use Nova\Foundation\Console\EnvironmentCommand;
use Nova\Foundation\Console\KeyGenerateCommand;
use Nova\Foundation\Console\RequestMakeCommand;
use Nova\Foundation\Console\ListenerMakeCommand;
use Nova\Foundation\Console\ProviderMakeCommand;
use Nova\Foundation\Console\HandlerEventCommand;
use Nova\Foundation\Console\ClearCompiledCommand;
use Nova\Foundation\Console\EventGenerateCommand;
use Nova\Foundation\Console\HandlerCommandCommand;
use Nova\Support\ServiceProvider;


class ForgeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = array(
        'ClearCompiled'  => 'command.clear-compiled',
        'CommandMake'    => 'command.command.make',
        'ConsoleMake'    => 'command.console.make',
        'EventMake'      => 'command.event.make',
        'Down'           => 'command.down',
        'Environment'    => 'command.environment',
        'HandlerCommand' => 'command.handler.command',
        'HandlerEvent'   => 'command.handler.event',
        'JobMake'        => 'command.job.make',
        'KeyGenerate'    => 'command.key.generate',
        'ListenerMake'   => 'command.listener.make',
        'ModelMake'      => 'command.model.make',
        'Optimize'       => 'command.optimize',
        'PolicyMake'     => 'command.policy.make',
        'ProviderMake'   => 'command.provider.make',
        'RequestMake'    => 'command.request.make',
        'RouteList'      => 'command.route.list',
        'Serve'          => 'command.serve',
        'Tinker'         => 'command.tinker',
        'Up'             => 'command.up',
        'ViewClear'      => 'command.view.clear',
    );

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";

            call_user_func_array(array($this, $method), array());
        }

        $this->commands(array_values($this->commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton('command.clear-compiled', function () {
            return new ClearCompiledCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCommandMakeCommand()
    {
        $this->app->singleton('command.command.make', function ($app) {
            return new CommandMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConsoleMakeCommand()
    {
        $this->app->singleton('command.console.make', function ($app) {
            return new ConsoleMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventGenerateCommand()
    {
        $this->app->singleton('command.event.generate', function () {
            return new EventGenerateCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventMakeCommand()
    {
        $this->app->singleton('command.event.make', function ($app) {
            return new EventMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerDownCommand()
    {
        $this->app->singleton('command.down', function () {
            return new DownCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEnvironmentCommand()
    {
        $this->app->singleton('command.environment', function () {
            return new EnvironmentCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerHandlerCommandCommand()
    {
        $this->app->singleton('command.handler.command', function ($app) {
            return new HandlerCommandCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerHandlerEventCommand()
    {
        $this->app->singleton('command.handler.event', function ($app) {
            return new HandlerEventCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerJobMakeCommand()
    {
        $this->app->singleton('command.job.make', function ($app) {
            return new JobMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton('command.key.generate', function ($app) {
            return new KeyGenerateCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerListenerMakeCommand()
    {
        $this->app->singleton('command.listener.make', function ($app) {
            return new ListenerMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerModelMakeCommand()
    {
        $this->app->singleton('command.model.make', function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerOptimizeCommand()
    {
        $this->app->singleton('command.optimize', function ($app) {
            return new OptimizeCommand($app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerProviderMakeCommand()
    {
        $this->app->singleton('command.provider.make', function ($app) {
            return new ProviderMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRequestMakeCommand()
    {
        $this->app->singleton('command.request.make', function ($app) {
            return new RequestMakeCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteListCommand()
    {
        $this->app->singleton('command.route.list', function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerServeCommand()
    {
        $this->app->singleton('command.serve', function () {
            return new ServeCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTinkerCommand()
    {
        $this->app->singleton('command.tinker', function () {
            return new TinkerCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpCommand()
    {
        $this->app->singleton('command.up', function () {
            return new UpCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerViewClearCommand()
    {
        $this->app->singleton('command.view.clear', function ($app) {
            return new ViewClearCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerPolicyMakeCommand()
    {
        $this->app->singleton('command.policy.make', function ($app) {
            return new PolicyMakeCommand($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
