<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;


class UpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Bring the application out of maintenance mode";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $basePath = $this->nova['path.storage'];

        @unlink($basePath .DS .'framework' .DS .'down');

        $this->info('Application is now live.');
    }

}
