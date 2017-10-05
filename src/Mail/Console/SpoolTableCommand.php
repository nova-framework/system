<?php

namespace Nova\Mail\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;

use Nova\Support\Str;


class SpoolTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mailer:spool:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the Mailer Spool database table';

    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;


    /**
     * Create a new queue job table command instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  \Nova\Foundation\Composer    $composer
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
        $table = $this->container['config']['mail.spool.table'];

        $tableClassName = Str::studly($table);

        $fullPath = $this->createBaseMigration($table);

        $stubPath = __DIR__ .DS . 'stubs' .DS .'spool.stub';

        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'], [$table, $tableClassName], $this->files->get($stubPath)
        );

        $this->files->put($fullPath, $stub);

        $this->info('Migration created successfully!');
    }

    /**
     * Create a base migration file for the table.
     *
     * @param  string  $table
     * @return string
     */
    protected function createBaseMigration($table = 'mailer_spool')
    {
        $name = 'create_'.$table.'_table';

        $path = $this->container['path'] .DS .'Database' .DS .'Migrations';

        return $this->container['migration.creator']->create($name, $path);
    }
}
