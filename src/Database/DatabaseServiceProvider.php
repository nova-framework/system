<?php
/**
 * DatabaseServiceProvider - Implements a Service Provider for Database.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Database;

use Nova\Database\ORM\Model;
use Nova\Database\ConnectionFactory;
use Nova\Database\DatabaseManager;
use Nova\Database\Classic\Model as ClassicModel;
use Nova\Support\ServiceProvider;


class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the Application events.
     *
     * @return void
     */
    public function boot()
    {
        $db = $this->app['db'];

        $events = $this->app['events'];

        // Setup the ORM Model.
        Model::setConnectionResolver($db);

        Model::setEventDispatcher($events);

        // Setup the classic Model.
        ClassicModel::setConnectionResolver($db);
    }

    /**
     * Register the Service Provider.
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
