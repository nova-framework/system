<?php
/**
 * EncryptionServiceProvider - Implements a Service Provider for Encryption.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Encryption;

use Nova\Encryption\Encrypter;
use Nova\Support\ServiceProvider;


class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('encrypter', function($app)
        {
            return new Encrypter($app['config']['app.key']);
        });
    }
}

