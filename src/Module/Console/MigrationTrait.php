<?php

namespace Nova\Module\Console;


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
     * @param string $module
     *
     * @return string
     */
    protected function getMigrationPath($module)
    {
        $modules = $this->nova['modules'];

        //
        $path = $modules->getModulePath($module);

        return $path .'Database' .DS .'Migrations' .DS;
    }
}
