<?php
/**
 * TranslationServiceProvider - Implements a Service Provider for Translation.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */


namespace Translation;

use Support\ServiceProvider;


class TranslationServiceProvider extends ServiceProvider
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
        $this->app->bindShared('translator', function($app)
        {
            $path = $app['path'].'/lang';

            $locale = $app['config']['app.locale'];

            return new Translator($path, $locale, 'en');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('translator');
    }
}
