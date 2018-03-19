<?php

namespace Nova\Package\Console;

use InvalidArgumentException;


trait MigrationTrait
{
    /**
     * Require (once) all migration files for the supplied Package.
     *
     * @param string $package
     */
    protected function requireMigrations($package)
    {
        $files = $this->container['files'];

        //
        $path = $this->getMigrationPath($package);

        $migrations = $files->glob($path.'*_*.php');

        foreach ($migrations as $migration) {
            $files->requireOnce($migration);
        }
    }

    /**
     * Get migration directory path.
     *
     * @param string $slug
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getMigrationPath($slug)
    {
        $packages = $this->container['packages'];

        //
        $package = $packages->where('slug', $slug);

        $path = $packages->resolveClassPath($package);

        return $path .'Database' .DS .'Migrations' .DS;
    }
}
