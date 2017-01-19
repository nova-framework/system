<?php

namespace Nova\View\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;

use RuntimeException;


class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'view:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear all compiled view files";

    /**
     * The File System instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
    * Get the cache path for the compiled views.
    *
    * @var string
    */
    protected $cachePath;


    /**
     * Create a new View Clear Command instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @return void
     */
    public function __construct(Filesystem $files, $cachePath)
    {
        parent::__construct();

        //
        $this->files = $files;

        $this->cachePath = $cachePath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $path = $this->getCachePath();

        if (! $this->files->exists($path)) {
            throw new RuntimeException('View path not found.');
        }

        foreach ($this->files->glob("{$path}/*.php") as $view) {
            $this->files->delete($view);
        }

        $this->info('Compiled views cleared!');
    }

    /**
     * Return the cache files path.
     *
     * @return string
     */
    protected function getCachePath()
    {
        return $this->cachePath;
    }

}
