<?php

namespace Nova\Database\Console\Migrations;

use Nova\Console\Command;


class BaseCommand extends Command
{

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $path = $this->input->getOption('path');

        // First, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        if (! is_null($path)) {
            return $this->container['path.base'] .DS .$path;
        }

        return $this->container['path'] .DS .'Database' .DS .'Migrations';
    }
}
