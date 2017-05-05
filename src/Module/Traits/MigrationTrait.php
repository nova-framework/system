<?php

namespace Nova\Module\Traits;

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
        $module = $modules->where('slug', $slug);

        $path = $modules->resolveClassPath($module);

        return $path .'Database' .DS .'Migrations' .DS;
    }
}
