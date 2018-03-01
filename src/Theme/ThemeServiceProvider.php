<?php

namespace Nova\Theme;

use Nova\Support\ServiceProvider;

use InvalidArgumentException;


class ThemeServiceProvider extends ServiceProvider
{

    /**
     * Register the Application's Themes.
     *
     * @return void
     */
    public function register()
    {
        $themes = $this->getInstalledThemes();

        $themes->each(function ($theme)
        {
            // The main Service Provider from a theme should be named like:
            // Themes\AdminLite\Providers\ThemeServiceProvider

            $provider = sprintf('%s\\Providers\\ThemeServiceProvider', $theme);

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }

    /**
     * Get the installed Themes.
     *
     * @return \Nova\Support\Collection
     */
    protected function getInstalledThemes()
    {
        $config = $this->app['config'];

        $path = $config->get('view.themes.path');

        try {
            $paths = collect(
                $this->app['files']->directories($path)
            );
        }
        catch (InvalidArgumentException $e) {
            $paths = collect();
        }

        $namespace = trim(
            $config->get('view.themes.namespace'), '\\'
        );

        return $paths->map(function ($path) use ($namespace)
        {
            return $namespace .'\\' .basename($path);
        });
    }
}
