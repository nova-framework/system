<?php

namespace Nova\Database;

use Nova\Database\Model as SimpleModel;
use Nova\Database\ORM\Model;
use Nova\Support\ServiceProvider;
use Nova\Database\Connectors\ConnectionFactory;


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
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->bindShared('db.factory', function($app)
        {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->bindShared('db', function($app)
        {
            return new DatabaseManager($app, $app['db.factory']);
        });
    }

}
