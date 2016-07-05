<?php

namespace Nova\Database\Console\Migrations;

use Nova\Console\Command;

class BaseCommand extends Command
{
    /**
     * Get the Path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        // A Requested Path.
        $path = $this->input->getOption('path');

        if ( ! is_null($path)) {
            return $this->nova['path.base'] .DS .$path;
        }

        // Vendor Package Path.
        $package = $this->input->getOption('package');

        if ( ! is_null($package)) {
            return $this->packagePath .DS .$package .DS .'src' .DS .'Migrations';
        }

        // Default Migrations Path.
        return $this->nova['path'] .DS .'Database' .DS .'Migrations';
    }

}
