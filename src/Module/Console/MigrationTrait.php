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
        $files = $this->nova['files'];

        //
        $path = $this->getMigrationPath($module);

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
        $modules = $this->nova['modules'];

        //
        $path = $modules->getModulePath($slug);

        return $path .'Database' .DS .'Migrations' .DS;
    }
}
