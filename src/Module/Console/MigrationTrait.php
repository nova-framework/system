<?php

namespace Nova\Module\Console;

use InvalidArgumentException;


trait MigrationTrait
{
    /**
     * Require (once) all migration files for the supplied module.
     *
     * @param string $module
     */
    protected function requireMigrations($module)
    {
        $path = $this->getMigrationPath($module);

        //
        $files = $this->container['files'];

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
        $modules = $this->container['modules'];

        if (! $modules->exists($slug)) {
            throw new InvalidArgumentException('Module does not exists.');
        }

        $path = $modules->getModulePath($slug);

        return $path .'Database' .DS .'Migrations' .DS;
    }
}
