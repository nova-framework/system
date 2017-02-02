<?php

namespace Nova\Foundation\Providers;

use Nova\Support\ServiceProvider;
use Nova\Foundation\ConfigPublisher;
use Nova\Foundation\Console\ConfigPublishCommand;


class PublisherServiceProvider extends ServiceProvider
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
        $this->registerConfigPublisher();

        $this->commands('command.config.publish');
    }

    /**
     * Register the configuration publisher class and command.
     *
     * @return void
     */
    protected function registerConfigPublisher()
    {
        $this->registerConfigPublishCommand();

        $this->app->bindShared('config.publisher', function($app)
        {
            $path = $app['path'] .DS .'Config';

            // Once we have created the configuration publisher, we will set the default
            // package path on the object so that it knows where to find the packages
            // that are installed for the application and can move them to the app.
            $publisher = new ConfigPublisher($app['files'], $path);

            $publisher->setPackagePath($app['path.base'] .DS .'vendor');

            return $publisher;
        });
    }

    /**
     * Register the configuration publish console command.
     *
     * @return void
     */
    protected function registerConfigPublishCommand()
    {
        $this->app->bindShared('command.config.publish', function($app)
        {
            $configPublisher = $app['config.publisher'];

            return new ConfigPublishCommand($configPublisher);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('config.publisher', 'command.config.publish');
    }

}
