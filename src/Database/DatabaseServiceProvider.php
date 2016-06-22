<?php

namespace Nova\Database;

use Nova\Database\Model as SimpleModel;
use Nova\Database\ORM\Model;
use Nova\Support\ServiceProvider;
use Nova\Database\Connections\ConnectionFactory;


class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        SimpleModel::setConnectionResolver($this->app['db']);

        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('db.factory', function($app)
        {
            return new ConnectionFactory($app);
        });

        $this->app->bindShared('db', function($app)
        {
            return new DatabaseManager($app, $app['db.factory']);
        });
    }

}
