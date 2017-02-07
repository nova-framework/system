<?php

namespace Nova\Foundation\Support\Providers;

use Nova\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the Application
     */
    protected function bootstrapFrom($path)
    {
        $app = $this->app;

        return require $path;
    }

}
