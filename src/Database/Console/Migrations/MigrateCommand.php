<?php

namespace Nova\Database\Console\Migrations;

use Nova\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class MigrateCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations';

    /**
     * The migrator instance.
     *
     * @var \Nova\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * The path to the packages directory (vendor).
     */
    protected $packagePath;

    /**
     * Create a new migration command instance.
     *
     * @param  \Nova\Database\Migrations\Migrator  $migrator
     * @param  string  $packagePath
     * @return void
     */
    public function __construct(Migrator $migrator, $packagePath)
    {
        parent::__construct();

        $this->migrator = $migrator;

        $this->packagePath = $packagePath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->prepareDatabase();

        $pretend = $this->input->getOption('pretend');

        $path = $this->getMigrationPath();

        $this->migrator->run($path, $pretend);

        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }

        if ($this->input->getOption('seed')) {
            $this->call('db:seed');
        }
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->migrator->setConnection($this->input->getOption('database'));

        if ( ! $this->migrator->repositoryExists()) {
            $options = array('--database' => $this->input->getOption('database'));

            $this->call('migrate:install', $options);
        }
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
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to migration files.', null),
            array('package', null, InputOption::VALUE_OPTIONAL, 'The package to migrate.', null),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
        );
    }

}
