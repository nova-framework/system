<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;


class DownCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'down';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Put the Application into Maintenance Mode";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $basePath = $this->nova['path.storage'];

        touch($basePath .DS .'down');

        $this->comment('Application is now in maintenance mode.');
    }

}
