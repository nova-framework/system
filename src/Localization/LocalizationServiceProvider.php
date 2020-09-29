<?php

namespace Nova\Localization;

use Nova\Localization\LanguageManager;
use Nova\Support\ServiceProvider;


class LocalizationServiceProvider extends ServiceProvider
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
        $this->app->singleton('language', function ($app)
        {
            $defaultNamespaces = array(
                // Namespace for the Framework path.
                'nova' => dirname(__DIR__) .DS .'Language',

                // Namespace for the Application path.
                'app' => APPPATH .'Language',

                // Namespace for the Shared path.
                'shared' => BASEPATH .'shared' .DS .'Language',
            );

            return new LanguageManager($app, $app['config']['app.locale'], $defaultNamespaces);
        });
    }

    protected function getDefaultLanguageHints()
    {
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('language');
    }
}
