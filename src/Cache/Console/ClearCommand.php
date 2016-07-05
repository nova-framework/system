<?php

namespace Nova\Cache\Console;

use Nova\Console\Command;
use Nova\Cache\CacheManager;
use Nova\Filesystem\Filesystem;


class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Flush the Application cache";

    /**
     * The Cache Manager instance.
     *
     * @var \Nova\Cache\CacheManager
     */
    protected $cache;

    /**
     * The File System instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new Cache Clear Command instance.
     *
     * @param  \Nova\Cache\CacheManager  $cache
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(CacheManager $cache, Filesystem $files)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->cache->flush();

        $this->files->delete($this->nova['config']['app.manifest'] .DS .'services.json');

        $this->info('Application cache cleared!');
    }

}
