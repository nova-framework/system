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

        if ( ! is_null($path)) {
            return $this->framework['path.base'] .'/' .$path;
        }

        return $this->framework['path'] .'/Database/Migrations';
    }

}
