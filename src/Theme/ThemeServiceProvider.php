<?php

namespace Nova\Theme;

use Nova\Support\ServiceProvider;

use InvalidArgumentException;


class ThemeServiceProvider extends ServiceProvider
{

    /**
     * Boot the Service Provider.
     */
    public function boot()
    {
        $namespace = trim(
            $this->app['config']->get('view.themes.namespace'), '\\'
        );

        $themes = $this->getInstalledThemes();

        $themes->each(function ($theme) use ($namespace)
        {
            // The main Service Provider from a theme should be named like:
            // Themes\AdminLite\Providers\ThemeServiceProvider

            $provider = sprintf('%s\\%s\\Providers\\ThemeServiceProvider', $namespace, $theme);

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }

    /**
     * Register the Application's Themes.
     *
     * @return void
     */
    public function register()
    {

    }

    protected function getInstalledThemes()
    {
        $themesPath = $this->app['config']->get('view.themes.path');

        try {
            $paths = $this->app['files']->directories($themesPath);
        }
        catch (InvalidArgumentException $e) {
            $paths = array();
        }

        $themes = array_map(function ($path)
        {
            return basename($path);

        }, $paths);

        return collect($themes);
    }
}
