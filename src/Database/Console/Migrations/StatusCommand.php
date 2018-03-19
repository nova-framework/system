<?php

namespace Nova\Database\Console\Migrations;

use Nova\Database\Migrations\Migrator;

use Symfony\Component\Console\Input\InputOption;


class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * The migrator instance.
     *
     * @var \Nova\Database\Migrations\Migrator
     */
    protected $migrator;


    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Nova\Database\Migrations\Migrator $migrator
     * @return \Nova\Database\Console\Migrations\StatusCommand
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->migrator->repositoryExists()) {
            return $this->error('No migrations found.');
        }

        $this->migrator->setConnection($this->input->getOption('database'));

        if (! is_null($path = $this->input->getOption('path'))) {
            $path = $this->container->basePath() .DS .$path;
        } else {
            $path = $this->getMigrationPath();
        }

        $ran = $this->migrator->getRepository()->getRan();

        //
        $migrations = array();

        foreach ($this->getAllMigrationFiles($path) as $migration) {
            $migrations[] = in_array($migration, $ran) ? array('<info>Y</info>', $migration) : array('<fg=red>N</fg=red>', $migration);
        }

        if (count($migrations) > 0) {
            $this->table(array('Ran?', 'Migration'), $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get all of the migration files.
     *
     * @param  string  $path
     * @return array
     */
    protected function getAllMigrationFiles($path)
    {
        return $this->migrator->getMigrationFiles($path);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),

            array('path', null, InputOption::VALUE_OPTIONAL, 'The path of migrations files to use.'),
        );
    }
}
