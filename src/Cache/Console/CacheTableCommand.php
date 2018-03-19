<?php

namespace Nova\Cache\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;


class CacheTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the Cache database table';

    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;


    /**
     * Create a new session table command instance.
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
    public function handle()
    {
        $fullPath = $this->createBaseMigration();

        $stubPath = realpath(__DIR__) .str_replace('/', DS, '/stubs/cache.stub');

        $this->files->put($fullPath, $this->files->get($stubPath));

        $this->info('Migration created successfully!');

        $this->call('optimize');
    }

    /**
     * Create a base migration file for the table.
     *
     * @return string
     */
    protected function createBaseMigration()
    {
        $name = 'create_cache_table';

        $path = $this->container['path'] .DS .'Database' .DS .'Migrations';

        return $this->container['migration.creator']->create($name, $path);
    }

}
