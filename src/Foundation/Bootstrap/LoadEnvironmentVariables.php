<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Config\EnvironmentVariables;
use Nova\Foundation\Application;


class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $env = $app['env'];

        $loader = new EnvironmentVariables(
            $app->getEnvironmentVariablesLoader()
        );

        $loader->load($env);
    }
}
