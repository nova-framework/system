<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\Console\OptimizeCommand;
use Nova\Foundation\Console\ClearCompiledCommand;


class OptimizeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerOptimizeCommand();

        $this->registerClearCompiledCommand();

        $this->commands('command.optimize', 'command.clear-compiled');
    }

    /**
     * Register the optimize command.
     *
     * @return void
     */
    protected function registerOptimizeCommand()
    {
        $this->app->bindShared('command.optimize', function($app)
        {
            return new OptimizeCommand($app['composer']);
        });
    }

    /**
     * Register the compiled file remover command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->bindShared('command.clear-compiled', function()
        {
            return new ClearCompiledCommand();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.optimize', 'command.clear-compiled');
    }

}
