<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Http\Request;
use Nova\Foundation\Application;


class SetRequestForConsole
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $url = $app->make('config')->get('app.url', 'http://localhost');

        $app->instance('request', Request::create($url, 'GET', array(), array(), array(), $_SERVER));
    }
}
