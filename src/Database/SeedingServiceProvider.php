<?php

namespace Nova\Database;

use Nova\Database\Seeder;

use Nova\Support\ServiceProvider;


class SeedingServiceProvider extends ServiceProvider
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
        $this->registerSeedCommand();

        $this->registerMakeCommand();

        $this->app->singleton('seeder', function($app)
        {
            return new Seeder();
        });

        $this->commands('command.seed', 'command.seeder.make');
    }

    /**
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function($app)
        {
            return new Console\SeedCommand($app['db']);
        });
    }

    /**
     * Register the seeder generator command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->app->singleton('command.seeder.make', function ($app)
        {
            return new Console\SeederMakeCommand($app['files'], $app['composer']);
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
            'seeder', 'command.seed', 'command.seeder.make'
        );
    }

}
