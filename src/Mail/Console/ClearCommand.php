<?php

namespace Nova\Mail\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;


class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'log:messages:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Flush the Mailer's messages log";

    /**
     * The File System instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new Cache Clear Command instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $path = $this->framework['path.storage'] .DS .'Logs' .DS .'messages.log';

        $this->files->put($path, "\n");

        $this->info('The Mailer\'s messages log was cleared!');
    }

}
